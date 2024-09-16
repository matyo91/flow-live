<?php

declare(strict_types=1);

namespace App\Command;

use App\Job\CarbonImageJob;
use App\Model\CarbonImage;
use Flow\Driver\AmpDriver;
use Flow\Driver\FiberDriver;
use Flow\Driver\ReactDriver;
use Flow\Driver\SpatieDriver;
use Flow\Driver\SwooleDriver;
use Flow\Flow\Flow;
use Flow\Ip;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(
    name: 'app:carbon-image',
    description: 'This allows use carbon.now.sh with Flow',
)]
class CarbonImageCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $driver = match (random_int(1, 4)) {
            1 => new AmpDriver(),
            2 => new FiberDriver(),
            3 => new ReactDriver(),
            4 => new SwooleDriver(),
            // 5 => new SpatieDriver(),
        };

        $flow = Flow::do(static function () use ($io) {
            yield new CarbonImageJob(__DIR__ . '/../../data/carbon-image/carbon-config.json');
            yield static function (CarbonImage $carbonImage) use ($io) {
                $io->info(sprintf('Finished %s %s', $carbonImage->code, $carbonImage->url));
            };
        }, ['driver' => $driver]);

        $ip1 = new Ip(new CarbonImage("<?php echo 'Hello, World!';", __DIR__ . '/../../hello.png'));
        $ip2 = new Ip(new CarbonImage("<?php

\$name = 'User';
echo 'Welcome, ' . htmlspecialchars(\$name) . '!';
echo '<br/>Today\\'s date is ' . date('Y-m-d');
", __DIR__ . '/../../welcome_user.png'));
        $ip3 = new Ip(new CarbonImage("<?php

\$colors = ['Red', 'Green', 'Blue'];
foreach(\$colors as \$color) {
    echo '<div style=\"color:' . strtolower(\$color) . '\">' . \$color . '</div>';
}
", __DIR__ . '/../../colors_list.png'));
        $ip4 = new Ip(new CarbonImage("<?php

\$error_message = 'Error 404: Page not found';
echo '<h1 style=\"color: red;\">' . \$error_message . '</h1>';
echo '<p>Please check the URL or return to the <a href=\"/\">homepage</a>.</p>';
", __DIR__ . '/../../error404_message.png'));
        $ip5 = new Ip(new CarbonImage("<?php

\$items = ['Home', 'About', 'Contact'];
echo '<ul>';
foreach(\$items as \$item) {
    echo '<li>' . \$item . '</li>';
}
echo '</ul>';
", __DIR__ . '/../../navigation_menu.png'));
        $flow($ip1);
        $flow($ip2);
        $flow($ip3);
        $flow($ip4);
        $flow($ip5);

        $flow->await();

        $io->success('Carbon Image is generated.');

        return Command::SUCCESS;
    }
}
