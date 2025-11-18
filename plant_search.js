
const CLIENT_ID = 'jjqeti1MWbDv6YPc1bKf62hPDE3W0hdfw19P3LEn';
const CLIENT_SECRET = '29X1eFII3UPIYDiJV3LfaafRV5vJ7qMWhm4bJcbgFYS3e228OdT1nF24K0vuF5cLIZPEqdIA0hlpsjrusFYC9OpUyDFX93b11ckYelJGJCjd8CAUEYh5oRD7PzLQd6Eh';

const searchBtn = document.getElementById('searchBtn');
const searchInput = document.getElementById('searchInput');
const resultTable = document.querySelector('#resultTable tbody');

async function getAccessToken() {
    const formData = new URLSearchParams();
    formData.append("grant_type", "client_credentials");
    formData.append("client_id", CLIENT_ID);
    formData.append("client_secret", CLIENT_SECRET);

    const response = await fetch('https://open.plantbook.io/api/v1/token/', {
        method: 'POST',
        body: formData,
    
    });

    const data = await response.json();
    return data.access_token;
}

async function searchPlant(alias) {
    const token = await getAccessToken();

    const response = await fetch(`https://open.plantbook.io/api/v1/plant/search?alias=${alias}`, {
            headers: {
              'Authorization': `Bearer ${token}`,
            }
            });

    const data = await response.json();
    return data.results || [];
}

function updateTable(plants) {
    resultTable.innerHTML = "";

    if(plants.length === 0) {
        resultTable.innerHTML = "<tr><td colspan='4'>No results found</td></tr>";
        return;
    }

    plants.forEach((plant) => {
        const row = document.createElement("tr");

        row.innerHTML = `
        <td>${plant.alias || "Not found"}</td>
        <td>${plant.pid || "Not found"}</td>
        <td>${plant.category || "Not found"}</td>
        <td><a href="plant_detail.php?pid=${plant.pid}" class="btn">View Details</a></td>
        `;

        resultTable.appendChild(row);
    });
}


searchBtn.addEventListener('click', async () => {
    const alias = searchInput.value.trim();
    if(!alias) return alert("Please enter a plant name");

    const plants = await searchPlant(alias);
    updateTable(plants);
});