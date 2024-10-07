const textures = {
    G: { name: 'water', color: '#1E90FF', pattern: createWaterPattern },    // Light blue for water
    H: { name: 'mountain', color: '#8B4513', pattern: createMountainPattern }, // Brown for mountains
    V: { name: 'village', color: '#FFD700', pattern: createVillagePattern },  // Gold for villages
    F: { name: 'forest', color: '#228B22', pattern: createForestPattern },   // Green for forests
    D: { name: 'desert', color: '#EDC9AF', pattern: createDesertPattern },   // Sandy color for desert
    L: { name: 'land', color: '#A0522D', pattern: createLandPattern }      // Sienna for land
};

// Function to create each texture pattern
function createWaterPattern(ctx, x, y, size) {
    ctx.fillStyle = '#87CEFA'; // Light blue
    ctx.fillRect(x, y, size, size);
    ctx.fillStyle = '#1E90FF'; // Water color
    for (let i = 0; i < 5; i++) {
        ctx.beginPath();
        ctx.arc(x + size / 2, y + size / 2, size / 4 * Math.random(), 0, Math.PI * 2);
        ctx.fill();
    }
}

function createMountainPattern(ctx, x, y, size) {
    ctx.fillStyle = '#8B4513'; // Mountain color
    ctx.fillRect(x, y, size, size);
    ctx.fillStyle = '#A0522D'; // Darker shade
    ctx.beginPath();
    ctx.moveTo(x + size / 2, y);
    ctx.lineTo(x + size, y + size);
    ctx.lineTo(x, y + size);
    ctx.fill();
}

function createVillagePattern(ctx, x, y, size) {
    ctx.fillStyle = '#FFD700'; // Village color
    ctx.fillRect(x, y, size, size);
    ctx.fillStyle = '#DAA520'; // Darker shade
    ctx.fillRect(x + size / 4, y + size / 4, size / 2, size / 2); // A house in the village
}

function createForestPattern(ctx, x, y, size) {
    ctx.fillStyle = '#228B22'; // Forest color
    ctx.fillRect(x, y, size, size);
    ctx.fillStyle = '#6B8E23'; // Darker green for trees
    for (let i = 0; i < 5; i++) {
        ctx.beginPath();
        ctx.moveTo(x + Math.random() * size, y + Math.random() * size);
        ctx.lineTo(x + 10 + Math.random() * 10, y + 10 + Math.random() * 10);
        ctx.lineTo(x + 20 + Math.random() * 10, y + Math.random() * size);
        ctx.fill();
    }
}

function createDesertPattern(ctx, x, y, size) {
    ctx.fillStyle = '#EDC9AF'; // Desert color
    ctx.fillRect(x, y, size, size);
    ctx.fillStyle = '#D2B48C'; // Darker shade for texture
    for (let i = 0; i < 3; i++) {
        ctx.beginPath();
        ctx.arc(x + Math.random() * size, y + Math.random() * size, size / 5, 0, Math.PI * 2);
        ctx.fill();
    }
}

function createLandPattern(ctx, x, y, size) {
    ctx.fillStyle = '#A0522D'; // Land color
    ctx.fillRect(x, y, size, size);
    ctx.fillStyle = '#CD853F'; // Darker shade
    ctx.fillRect(x + 10, y + 10, size - 20, size - 20); // Inner texture
}

// Function to generate random input letters
function getRandomInput(rows, columns) {
    const keys = Object.keys(textures);
    const randomInput = [];
    for (let i = 0; i < rows; i++) {
        let row = '';
        for (let j = 0; j < columns; j++) {
            const randomIndex = Math.floor(Math.random() * keys.length);
            row += keys[randomIndex]; // Append a random texture key
        }
        randomInput.push(row); // Add the row to the random input
    }
    return randomInput;
}

// Generate random input with 3 rows and 3 columns
const inputLetters = getRandomInput(3, 3);

// Function to generate texture
function generateTexture(rows) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const tileSize = 100; // Size of each texture tile

    // Set canvas size based on the number of rows and columns
    const numberOfRows = rows.length;
    const numberOfColumns = rows[0].length;

    canvas.width = tileSize * numberOfColumns;
    canvas.height = tileSize * numberOfRows;

    // Fill the canvas with the corresponding texture colors
    for (let row = 0; row < numberOfRows; row++) {
        for (let col = 0; col < numberOfColumns; col++) {
            const letter = rows[row][col];
            const texture = textures[letter]; // Only get texture if it exists
            if (texture) {
                // Use the pattern function to fill the texture
                texture.pattern(ctx, col * tileSize, row * tileSize, tileSize);
            }
        }
    }

    return canvas.toDataURL(); // Return image data URL
}

// Generate and display the texture image
const textureImage = generateTexture(inputLetters);

// Create an img element
const img = document.createElement('img');
img.src = textureImage;
img.alt = 'Generated Texture';
img.style.margin = '10px'; // Add some margin

// Append the img element to the document body
document.body.appendChild(img);
