<?php

declare(strict_types=1);

namespace App\Command;

use App\EnumType\SymfonyCertification\CertificationEnumType;
use App\EnumType\SymfonyCertification\QuestionEnumType;
use App\Job\SymfonyCertification\DocumentsJobs;
use App\Job\SymfonyCertification\TopicsJobs;
use Flow\Exception\RuntimeException;
use Flow\Flow\YFlow;
use Flow\FlowFactory;
use Flow\Ip;
use LLPhant\Chat\OllamaChat;
use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\FileSystem\FileSystemVectorStore;
use LLPhant\OllamaConfig;
use LLPhant\OpenAIConfig;
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Cache\CacheInterface;
use ValueError;

use function count;
use function sprintf;

#[AsCommand(
    name: 'app:symfony-certification',
    description: 'Train for symfony certification',
)]
class SymfonyCertificationCommand extends Command
{
    public function __construct(
        private CacheInterface $cache,
        private FlowFactory $flowFactory,
        private DocumentsJobs $documentsJob,
        private TopicsJobs $topicsJob,
        #[Autowire('%kernel.cache_dir%/symfony_certification')]
        private string $cacheDir,
        #[Autowire('%env(OPENAI_API_KEY)%')]
        private string $openAiApiKey,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('certification', InputArgument::REQUIRED, 'The certification type')
            ->addOption('model', null, InputArgument::OPTIONAL, 'The embedding model to use', 'openai-3-small')
            ->addOption('scrap', null, InputArgument::OPTIONAL, 'Force document scraping')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $certificationType = CertificationEnumType::from($input->getArgument('certification'));
        } catch (ValueError $e) {
            $io->error('Invalid certification specified. Available options are: ' . implode(', ', array_column(CertificationEnumType::cases(), 'value')));

            return Command::FAILURE;
        }

        $flow = $this->flowFactory->create(function () use ($io, $input) {
            // setup questions datas
            yield function ($certificationType) use ($io, $input) {
                if ($input->getOption('model') === 'openai-3-small') {
                    $config = new OpenAIConfig();
                    $config->apiKey = $this->openAiApiKey;
                    $embeddingGenerator = new OpenAI3SmallEmbeddingGenerator($config);
                    $chat = new OpenAIChat($config);
                } elseif ($input->getOption('model') === 'llama3.2') {
                    $config = new OllamaConfig();
                    $config->model = 'llama3.2';
                    $embeddingGenerator = new OllamaEmbeddingGenerator($config);
                    $chat = new OllamaChat($config);
                } else {
                    throw new ValueError('Invalid model specified. Available options are: openai-3-small, llama3.2');
                }

                $filesystem = new Filesystem();
                $filesystem->mkdir($this->cacheDir);
                $vectorStoreFilePath = $this->cacheDir . '/' . $certificationType->value . '-vectorStore.json';
                $memoryVectorStore = new FileSystemVectorStore($vectorStoreFilePath);

                if ($input->getOption('scrap')) {
                    $io->info('Getting documents...');
                    $documents = ($this->documentsJob)($certificationType);
                    $io->info(sprintf('Found %d documents, split documents...', count($documents)));
                    $splitDocuments = DocumentSplitter::splitDocuments($documents, 256); // some inputs can have more than 8192 tokens, prefer use to split documents with https://cookbook.openai.com/examples/how_to_count_tokens_with_tiktoken
                    $io->info(sprintf('Embedding %d splited documents...', count($splitDocuments)));
                    $progressBar = $io->createProgressBar(count($splitDocuments));
                    $progressBar->setFormat('debug');
                    $progressBar->start();
                    $embeddedChunk = [];
                    foreach ($splitDocuments as $document) {
                        $cacheKey = sprintf('symfony_certification_doc_embedding_%s_%s', $input->getOption('model'), $document->hash);
                        $embeddedDoc = $this->cache->get($cacheKey, static function () use ($embeddingGenerator, $document) { // we cache embeding retrival in case of re-run the command
                            return $embeddingGenerator->embedDocument($document);
                        });
                        $embeddedChunk[] = $embeddedDoc;
                        $progressBar->advance();
                    }
                    $progressBar->finish();
                    $io->newLine(2);
                    $memoryVectorStore->addDocuments($embeddedChunk);
                    $io->success('Finished vectorizing documents');
                }

                $qa = new QuestionAnswering(
                    $memoryVectorStore,
                    $embeddingGenerator,
                    $chat,
                );

                $topics = ($this->topicsJob)($certificationType);

                // Based on https://certification.symfony.com certification requirements
                $totalQuestions = match ($certificationType) {
                    CertificationEnumType::SYMFONY_7 => 75,
                    CertificationEnumType::TWIG_3 => 45,
                    CertificationEnumType::SYLIUS_1 => 60,
                    default => throw new ValueError('Unsupported certification type'),
                };

                return [$certificationType, $topics, $qa, 0, 0, $totalQuestions];
            };
            // process the certification quizz
            yield new YFlow(static function ($questionLoop) use ($io) {
                return static function ($data) use ($questionLoop, $io) {
                    [$certificationType, $topics, $qa, $i, $score, $totalQuestions] = $data;

                    $io->info(sprintf('Question %d/%d (Score: %d/%d)', $i + 1, $totalQuestions, $score, $i));
                    $io->info('Fetching question...');

                    $certificationSubject = match ($certificationType) {
                        CertificationEnumType::SYMFONY_7 => 'Symfony 7',
                        CertificationEnumType::TWIG_3 => 'Twig 3',
                        CertificationEnumType::SYLIUS_1 => 'Sylius 1',
                        default => throw new ValueError('Unsupported certification type'),
                    };

                    // Randomly select a topic
                    $randomTopic = $topics[array_rand($topics)];
                    $randomTopicDetails = $randomTopic->topic . ': ' . implode(', ', $randomTopic->items);

                    // Generate the question based on the random topic
                    $questionType = QuestionEnumType::cases()[array_rand(QuestionEnumType::cases())];
                    $prompt = match ($questionType) {
                        QuestionEnumType::TRUE_FALSE => sprintf(
                            "Based on the subject '%s' and the topic '%s', generate a challenging true/false question that tests deep understanding. Format your response as JSON with the following structure:
                            {
                                'question': 'The question text',
                                'correct_answer': true/false,
                                'explanation': 'Detailed explanation with reference to the documentation and link'
                            }",
                            $certificationSubject,
                            $randomTopicDetails
                        ),
                        QuestionEnumType::SINGLE_ANSWER => sprintf(
                            "Based on the subject '%s' and the topic '%s', generate a multiple-choice question that tests deep understanding with 4-6 options where only one is correct. Format your response as JSON with the following structure:
                            {
                                'question': 'The question text',
                                'options': ['option1', 'option2', 'option3', 'option4'],
                                'correct_answer': 'The correct option',
                                'explanation': 'Detailed explanation with reference to the documentation and link'
                            }",
                            $certificationSubject,
                            $randomTopicDetails
                        ),
                        QuestionEnumType::MULTIPLE_CHOICE => sprintf(
                            "Based on the subject '%s' and the topic '%s', generate a multiple-choice question that tests deep understanding with 4-6 options where multiple answers are correct. Format your response as JSON with the following structure:
                            {
                                'question': 'The question text',
                                'options': ['option1', 'option2', 'option3', 'option4'],
                                'correct_answers': ['correct1', 'correct2'],
                                'explanation': 'Detailed explanation with reference to the documentation and link'
                            }",
                            $certificationSubject,
                            $randomTopicDetails
                        ),
                    };

                    $response = $qa->answerQuestion($prompt);
                    $questionData = json_decode(trim($response, "```json\n```"), true);

                    $io->section(sprintf('Topic: %s', $randomTopic->topic));
                    $io->writeln($questionData['question']);
                    $io->newLine();

                    if ($questionType === QuestionEnumType::TRUE_FALSE) {
                        $answer = $io->confirm('Your answer?');
                        $isCorrect = $answer === $questionData['correct_answer'];

                        $io->newLine();
                        if ($isCorrect) {
                            $io->success('Your answer is correct!');
                            $score++;
                        } else {
                            $io->error('Your answer is incorrect!');
                        }
                    } elseif ($questionType === QuestionEnumType::SINGLE_ANSWER) {
                        $options = $questionData['options'];
                        $answer = $io->choice('Your answer (single choice)?', $options);
                        $isCorrect = $answer === $questionData['correct_answer'];

                        $io->newLine();
                        if ($isCorrect) {
                            $io->success('Your answer is correct!');
                            $score++;
                        } else {
                            $io->error('Your answer is incorrect!');
                            $io->writeln(sprintf('The correct answer was: %s', $questionData['correct_answer']));
                        }
                    } elseif ($questionType === QuestionEnumType::MULTIPLE_CHOICE) {
                        $options = $questionData['options'];
                        $answers = $io->choice('Your answers (multiple choices)?', $options, multiSelect: true);

                        $correctAnswers = $questionData['correct_answers'];
                        sort($answers);
                        sort($correctAnswers);
                        $isCorrect = $answers === $correctAnswers;

                        $io->newLine();
                        if ($isCorrect) {
                            $io->success('Your answer is correct!');
                            $score++;
                        } else {
                            $io->error('Your answer is incorrect!');
                            $io->writeln('The correct answers were:');
                            foreach ($correctAnswers as $answer) {
                                $io->writeln('- ' . $answer);
                            }
                        }
                    }

                    $io->newLine();
                    $io->section('Score Summary');
                    $io->writeln(sprintf(
                        'Current score: %d correct answers out of %d questions (%.1f%%)',
                        $score,
                        $i + 1,
                        ($score / ($i + 1)) * 100
                    ));

                    $io->section('Explanation');
                    $io->writeln($questionData['explanation']);
                    if ($i < $totalQuestions) {
                        return $questionLoop([$certificationType, $topics, $qa, $i + 1, $score, $totalQuestions]);
                    }

                    $io->success(sprintf('Final score: %d/20 (%.1f%%)', $score, ($score / 20) * 100));

                    return null;
                };
            });
        }, [
            'errorJob' => static function (RuntimeException $exception) {
                throw $exception->getPrevious();
            },
        ]);

        $flow(new Ip($certificationType));
        $flow->await();

        return Command::SUCCESS;
    }
}
