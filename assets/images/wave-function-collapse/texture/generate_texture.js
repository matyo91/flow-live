const textures = {
    G: { name: 'water', color: [30, 144, 255], pattern: createWaterPattern },
    H: { name: 'mountain', color: [139, 69, 19], pattern: createMountainPattern },
    V: { name: 'village', color: [255, 215, 0], pattern: createVillagePattern },
    F: { name: 'forest', color: [34, 139, 34], pattern: createForestPattern },
    D: { name: 'desert', color: [237, 201, 175], pattern: createDesertPattern },
    L: { name: 'land', color: [160, 82, 45], pattern: createLandPattern },
    A: { name: 'lava', color: [255, 69, 0], pattern: createLavaPattern },
    R: { name: 'road', color: [128, 128, 128], pattern: createRoadPattern },
    I: { name: 'ice', color: [173, 216, 230], pattern: createIcePattern }
};

function createWaterPattern(ctx, x, y, size) {
    const gradient = ctx.createLinearGradient(x, y, x, y + size);
    gradient.addColorStop(0, 'rgba(30, 144, 255, 0.9)');
    gradient.addColorStop(1, 'rgba(30, 144, 255, 0.5)');
    ctx.fillStyle = gradient;
    ctx.fillRect(x, y, size, size);
    
    ctx.fillStyle = 'rgba(255, 255, 255, 0.4)'; // Light white for waves
    for (let i = 0; i < 15; i++) {
        const radius = Math.random() * 15 + 5;
        ctx.beginPath();
        ctx.arc(x + Math.random() * size, y + Math.random() * size, radius, 0, Math.PI * 2);
        ctx.fill();
    }
}

function createMountainPattern(ctx, x, y, size) {
    const gradient = ctx.createLinearGradient(x, y, x, y + size);
    gradient.addColorStop(0, 'rgba(139, 69, 19, 0.8)'); // Mountain color
    gradient.addColorStop(1, 'rgba(160, 82, 45, 0.7)'); // Lighter shade
    ctx.fillStyle = gradient;
    ctx.fillRect(x, y, size, size);
    ctx.fillStyle = 'rgba(255, 255, 255, 0.3)'; // Snow caps
    ctx.beginPath();
    ctx.moveTo(x + size / 2, y);
    ctx.lineTo(x + size, y + size);
    ctx.lineTo(x, y + size);
    ctx.fill();
}

function createVillagePattern(ctx, x, y, size) {
    const gradient = ctx.createLinearGradient(x, y, x, y + size);
    gradient.addColorStop(0, 'rgba(255, 215, 0, 0.9)'); // Base village color
    gradient.addColorStop(1, 'rgba(218, 165, 32, 0.9)'); // Darker shade
    ctx.fillStyle = gradient;
    ctx.fillRect(x, y, size, size);
}

function createForestPattern(ctx, x, y, size) {
    ctx.fillStyle = 'rgba(34, 139, 34, 0.9)'; // Forest base
    ctx.fillRect(x, y, size, size);
    
    for (let i = 0; i < 10; i++) {
        const treeX = x + Math.random() * size;
        const treeY = y + Math.random() * size;
        ctx.fillStyle = 'rgba(107, 142, 35, 0.9)'; // Darker green for trees
        ctx.beginPath();
        ctx.moveTo(treeX, treeY);
        ctx.lineTo(treeX + 10, treeY + 30);
        ctx.lineTo(treeX + 20, treeY);
        ctx.fill();
    }
}

function createDesertPattern(ctx, x, y, size) {
    const gradient = ctx.createLinearGradient(x, y, x, y + size);
    gradient.addColorStop(0, 'rgba(237, 201, 175, 0.9)'); // Desert color
    gradient.addColorStop(1, 'rgba(210, 180, 140, 0.7)'); // Darker shade
    ctx.fillStyle = gradient;
    ctx.fillRect(x, y, size, size);
    
    for (let i = 0; i < 5; i++) {
        const arcX = x + Math.random() * size;
        const arcY = y + Math.random() * size;
        ctx.fillStyle = 'rgba(210, 180, 140, 0.5)'; // Sand details
        ctx.beginPath();
        ctx.arc(arcX, arcY, 10, 0, Math.PI * 2);
        ctx.fill();
    }
}

function createLandPattern(ctx, x, y, size) {
    ctx.fillStyle = 'rgba(160, 82, 45, 0.9)'; // Land color
    ctx.fillRect(x, y, size, size);
    
    for (let i = 0; i < 5; i++) {
        const detailX = x + Math.random() * size;
        const detailY = y + Math.random() * size;
        ctx.fillStyle = 'rgba(205, 133, 63, 0.6)'; // Inner texture
        ctx.beginPath();
        ctx.arc(detailX, detailY, 5, 0, Math.PI * 2);
        ctx.fill();
    }
}

function createLavaPattern(ctx, x, y, size) {
    const gradient = ctx.createLinearGradient(x, y, x, y + size);
    gradient.addColorStop(0, 'rgba(255, 0, 0, 0.9)');
    gradient.addColorStop(1, 'rgba(255, 69, 0, 0.7)');
    ctx.fillStyle = gradient;
    ctx.fillRect(x, y, size, size);
}

function createRoadPattern(ctx, x, y, size) {
    ctx.fillStyle = 'rgba(128, 128, 128, 0.9)'; // Road color
    ctx.fillRect(x, y, size, size);
    ctx.fillStyle = 'rgba(255, 255, 255, 0.5)'; // Road markings
    for (let i = 0; i < 4; i++) {
        ctx.fillRect(x + size / 4 + (size / 2) * (i % 2), y + size / 2 - 5 + (i % 2) * 10, size / 8, 10);
    }
}

function createIcePattern(ctx, x, y, size) {
    const gradient = ctx.createLinearGradient(x, y, x, y + size);
    gradient.addColorStop(0, 'rgba(173, 216, 230, 0.8)'); // Light blue
    gradient.addColorStop(1, 'rgba(135, 206, 250, 0.5)'); // Lighter blue
    ctx.fillStyle = gradient;
    ctx.fillRect(x, y, size, size);
    ctx.fillStyle = 'rgba(255, 255, 255, 0.4)'; // Frosty overlay
    for (let i = 0; i < 10; i++) {
        ctx.beginPath();
        ctx.arc(x + Math.random() * size, y + Math.random() * size, 5, 0, Math.PI * 2);
        ctx.fill();
    }
}

// Function to generate the final texture
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
                
                // Blend with adjacent tiles
                if (col > 0) {
                    blendTextures(ctx, col * tileSize, row * tileSize, textures[rows[row][col - 1]]);
                }
                if (row > 0) {
                    blendTextures(ctx, col * tileSize, row * tileSize, textures[rows[row - 1][col]]);
                }
            }
        }
    }

    return canvas.toDataURL(); // Return image data URL
}

// Function to blend two textures
function blendTextures(ctx, x, y, adjacentTexture) {
    if (!adjacentTexture) return;
    const gradient = ctx.createLinearGradient(x, y, x + 100, y + 100);
    gradient.addColorStop(0, 'rgba(255, 255, 255, 0.2)'); // White with transparency
    gradient.addColorStop(1, `rgba(${adjacentTexture.color.join(',')}, 0.5)`); // Blend with adjacent color
    ctx.fillStyle = gradient;
    ctx.fillRect(x, y, 100, 100); // Fill with blended color
}

// Generate random input with specified rows and columns
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

// Generate random input for a 3x3 texture
const inputLetters = getRandomInput(3, 3);

// Generate and display the texture image
const textureImage = generateTexture(inputLetters);

// Create an img element
const img = document.createElement('img');
img.src = textureImage;
img.alt = 'Generated Realistic Texture';
img.style.margin = '10px'; // Add some margin

// Append the img element to the document body
document.body.appendChild(img);
