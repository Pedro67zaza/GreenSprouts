<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plant Search - Agriculture Website</title>

    <script src="https://kit.fontawesome.com/67a65874b9.js" crossorigin="anonymous"></script>
    <!-- N8N Chat Widget - Single Import -->
    <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #e8f5e9;
            min-height: 100vh;
            padding: 20px;
        }
        

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f4f9f4;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        h1 {
            color: #2d3748;
            text-align: center;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }

        .subtitle {
            text-align: center;
            color: #718096;
            margin-bottom: 30px;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        #plantInput {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        #plantInput:focus {
            outline: none;
            border-color: #2e7d32;
        }

        #searchBtn {
            padding: 15px 30px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        #searchBtn:hover {
            background: #c8e6c9;
            color: #2e7d32;
            border: 2px solid #2e7d32;
            transition: all 0.4s ease;
        }

        #searchBtn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #718096;
            display: none;
        }

        .spinner {
            border: 4px solid #e2e8f0;
            border-top: 4px solid #2e7d32;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }

        .results {
            display: none;
        }

        .plant-header {
            background: #2e7d32;
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .plant-header h2 {
            font-size: 2em;
            margin-bottom: 5px;
        }

        .scientific-name {
            font-style: italic;
            opacity: 0.9;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .info-card {
            background: #f7fafc;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #2e7d32;
        }

        .info-card h3 {
            color: #2d3748;
            font-size: 0.9em;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .info-card p {
            color: #4a5568;
            font-size: 1.1em;
        }

        .detail-section {
            background: #f7fafc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .detail-section h3 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        .detail-section p {
            color: #4a5568;
            line-height: 1.6;
        }

        .range-display {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }

        .range-bar {
            flex: 1;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            position: relative;
            overflow: hidden;
        }

        .range-fill {
            position: absolute;
            height: 100%;
            background: #2e7d32;
            border-radius: 4px;
        }

        .tooltip {
            position: relative;
            border-bottom: 1px dotted black;
            display: inline-block;
            
        }        
        
        .tooltip .tooltiptext {  
            visibility: hidden;
            width: 200px;
            background-color: #555;
            color: white;
            text-align: center;
            border-radius: 6px;
            padding: 5px 0;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #555 transparent transparent transparent;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        #backIcon {
            text-decoration: none;
            color: black;
            font-size: 15px;
        }

            /* Ensure chat widget is accessible and visible */
        /* N8N Chat Widget Custom Styling */
        #n8n-chat {
            z-index: 9999 !important;
        }




    </style>
</head>
<body>
    <section class="main-content">
    <div class="container">
        <a href="logbookfirstpage.php"><i class="fa-solid fa-backward" id="backIcon">Go back</i></a>
        <h1>GreenSprouts Plant Repository</h1>
        <p class="subtitle">Discover detailed information about plants</p>

        <div class="search-box">
            <input type="text" id="plantInput" placeholder="Enter plant name (e.g., tomato, rose, basil)">
            <button id="searchBtn">Search</button>
        </div>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Searching for plant information...</p>
        </div>

        <div class="error" id="error"></div>

        <div class="results" id="results"></div>
    </div>

    </section>

    <script>
        const API_BASE = 'https://open.plantbook.io/api/v1';
        const API_TOKEN = '0c9e0231393aabca57ba8e0199b17ded5044f785';

        const plantInput = document.getElementById('plantInput');
        const searchBtn = document.getElementById('searchBtn');
        const loading = document.getElementById('loading');
        const error = document.getElementById('error');
        const results = document.getElementById('results');

        // Search on button click
        searchBtn.addEventListener('click', searchPlant);

        // Search on Enter key
        plantInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchPlant();
            }
        });

        async function searchPlant() {
            const plantName = plantInput.value.trim();
            
            if (!plantName) {
                showError('Please enter a plant name');
                return;
            }

            // Reset UI
            error.style.display = 'none';
            results.style.display = 'none';
            loading.style.display = 'block';
            searchBtn.disabled = true;

            try {
                // Step 1: Get scientific name from alias
                const scientificName = await getScientificName(plantName);
                
                // Step 2: Get plant details using scientific name
                const plantDetails = await getPlantDetails(scientificName);
                
                // Step 3: Display results
                displayResults(plantDetails);
                
            } catch (err) {
                showError(err.message);
            } finally {
                loading.style.display = 'none';
                searchBtn.disabled = false;
            }
        }

        async function getScientificName(alias) {
            const response = await fetch(`${API_BASE}/plant/search?alias=${encodeURIComponent(alias)}`, {
                headers: {
                    'Authorization': `Token ${API_TOKEN}`
                }
            });

            if (!response.ok) {
                throw new Error('Plant not found. Please try another name.');
            }

            const data = await response.json();
            
            if (!data.results || data.results.length === 0) {
                throw new Error('No plants found matching that name.');
            }

            // Return the scientific name (pid) of the first result
            return data.results[0].pid;
        }

        async function getPlantDetails(scientificName) {
            const response = await fetch(`${API_BASE}/plant/detail/${encodeURIComponent(scientificName)}/`, {
                headers: {
                    'Authorization': `Token ${API_TOKEN}`
                }
            });

            if (!response.ok) {
                throw new Error('Unable to fetch plant details.');
            }

            return await response.json();
        }

        function displayResults(plant) {
            const html = `
                <div class="plant-header">
                    <h2>${plant.display_pid || plant.pid}</h2>
                    <p class="scientific-name">${plant.pid}</p>
                </div>

                <div class="info-grid">
                    ${plant.max_light_lux ? `
                    <div class="info-card">
                        <div class="tooltip">
                        <i class="fa-solid fa-sun"><span class="tooltiptext">The amount of light a plant needs to grow, measured in lux</span></i>
                        </div>
                        <h3>Light Requirements</h3>
                        <p>${plant.min_light_lux || 0} - ${plant.max_light_lux} lux</p>
                    </div>
                    ` : ''}

                    ${plant.max_temp ? `
                    <div class="info-card">
                        <div class="tooltip">
                        <i class="fa-solid fa-temperature-three-quarters"><span class="tooltiptext">The ideal air warmth for plant growth, measured in °C</span></i>
                        </div>
                        <h3>Temperature Range</h3>
                        <p>${plant.min_temp || 0}°C - ${plant.max_temp}°C</p>
                    </div>
                    ` : ''}

                    ${plant.max_soil_moist ? `
                    <div class="info-card">
                        <div class="tooltip">
                        <i class="fa-solid fa-droplet"><span class="tooltiptext">The amount of water in the soil, measured in percentage (%)</span></i>
                        </div>
                        <h3>Soil Moisture</h3>
                        <p>${plant.min_soil_moist || 0}% - ${plant.max_soil_moist}%</p>
                    </div>
                    ` : ''}

                    ${plant.max_soil_ec ? `
                    <div class="info-card">
                        <div class="tooltip">
                        <i class="fa-solid fa-bolt"><span class="tooltiptext">Indicates nutrient concentration in the soil, measured in dS/m)</span></i>
                        </div>
                        <h3>Soil EC</h3>
                        <p>${plant.min_soil_ec || 0} - ${plant.max_soil_ec}</p>
                    </div>
                    ` : ''}

                    ${plant.max_env_humid ? `
                <div class="info-card">
                    <div class="tooltip">
                        <i class="fa-solid fa-wind"><span class="tooltiptext">The amount of moisture in the air, measured in percentage (%)</span></i>
                        </div>
                    <h3>Atmospheric Humidity</h3>
                    <p>${plant.min_env_humid || 0}% - ${plant.max_env_humid}%</p>
                </div>
                ` : ''}

                </div>


                ${plant.category ? `
                <div class="detail-section">
                    <h3>Category</h3>
                    <p>${plant.category}</p>
                </div>
                ` : ''}

                ${plant.image_url ? `
                <div class="detail-section">
                    <h3>Plant Image</h3>
                    <img src="${plant.image_url}" alt="${plant.pid}" style="max-width: 100%; border-radius: 10px; margin-top: 10px;">
                </div>
                ` : ''}
            `;

            results.innerHTML = html;
            results.style.display = 'block';
        }

        function showError(message) {
            error.textContent = message;
            error.style.display = 'block';
        }
    </script>
    <!-- N8N Chat Widget - Initialize after page loads -->
    <script type="module">
        import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

        // Wait for DOM to be ready
        window.addEventListener('DOMContentLoaded', () => {
            createChat({
                webhookUrl: 'http://localhost:5677/webhook/0f6e0f89-8586-4b2b-afca-40e411f00bcf/chat',
                initialMessages: [
                    'Hello! How can I help you with your agricultural journey today?'
                ],
                i18n: {
                    en: {
                        title: 'GreenSprouts Assistant',
                        subtitle: 'Ask me anything about agriculture',
                        footer: '',
                        getStarted: 'Start Chat',
                        inputPlaceholder: 'Type your message...',
                    }
                }

            });
        });
    </script>
</body>
</html>