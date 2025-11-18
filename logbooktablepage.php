<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db_connect.php";

//Validate the project ID

if (!isset($_GET['projectID']) || !is_numeric($_GET['projectID'])) {
    die("Error: projectID not found or invalid in URL.");
}

$projectID = intval($_GET['projectID']);

$query = $connect->prepare("SELECT projectName FROM projects WHERE projectID = ? AND userId = ?");
$query->bind_param("ii", $projectID, $_SESSION['userId']);
$query->execute();
$result = $query->get_result();

if($result-> num_rows === 0) {
    die("Error: Project not found or access denied.");
}
$row = $result->fetch_assoc();
$projectTitle = htmlspecialchars($row['projectName']);
$query->close();

// Pagination setup
$records_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$current_page = max(1, $current_page); // Ensure page is at least 1

// Get total number of records for this project
$count_query = $connect->prepare("SELECT COUNT(*) as total FROM records WHERE projectID = ?");
$count_query->bind_param("i", $projectID);
$count_query->execute();
$count_result = $count_query->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$count_query->close();

// Calculate total pages
$total_pages = ceil($total_records / $records_per_page);

// Calculate offset for SQL query
$offset = ($current_page - 1) * $records_per_page;

// Get statistics for this project
$stats_query = $connect->prepare("
    SELECT 
        COUNT(*) as total_entries,
        SUM(CASE WHEN waterAmount IS NOT NULL AND waterAmount != 'none' THEN 1 ELSE 0 END) as watered_entries,
        SUM(CASE WHEN fertilizerType IS NOT NULL AND fertilizerType != 'none' THEN 1 ELSE 0 END) as fertilized_entries,
        SUM(CASE WHEN pesticide IS NOT NULL AND pesticide != '-' THEN 1 ELSE 0 END) as pesticide_entries,
        SUM(CASE WHEN recordImage IS NOT NULL AND recordImage != '-' THEN 1 ELSE 0 END) as entries_with_images,
        AVG(CASE WHEN height IS NOT NULL AND height != '-' THEN CAST(height AS DECIMAL(10,2)) END) as avg_height,
        AVG(CASE WHEN width IS NOT NULL THEN CAST(width AS DECIMAL(10,2)) END) as avg_width,
        MAX(recordDate) as latest_entry,
        MIN(recordDate) as first_entry
    FROM records 
    WHERE projectID = ?
");
$stats_query->bind_param("i", $projectID);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();
$stats_query->close();

// Get water level distribution
$water_dist_query = $connect->prepare("
    SELECT 
        waterAmount,
        COUNT(*) as count
    FROM records 
    WHERE projectID = ? AND waterAmount IS NOT NULL AND waterAmount != 'none'
    GROUP BY waterAmount
    ORDER BY count DESC
");
$water_dist_query->bind_param("i", $projectID);
$water_dist_query->execute();
$water_dist_result = $water_dist_query->get_result();
$water_distribution = array();
while($row = $water_dist_result->fetch_assoc()) {
    $water_distribution[] = $row;
}
$water_dist_query->close();

// Find most common water level
$most_common_water = '';
if (!empty($water_distribution)) {
    $most_common_water = $water_distribution[0]['waterAmount'];
}

// Get time series data for charts
$chart_query = $connect->prepare("
    SELECT 
        recordDate,
        recordName,
        CASE WHEN height IS NOT NULL AND height != '-' THEN CAST(height AS DECIMAL(10,2)) ELSE NULL END as height,
        CASE WHEN width IS NOT NULL THEN CAST(width AS DECIMAL(10,2)) ELSE NULL END as width,
        waterAmount
    FROM records 
    WHERE projectID = ?
    ORDER BY recordDate ASC
");
$chart_query->bind_param("i", $projectID);
$chart_query->execute();
$chart_result = $chart_query->get_result();

$chart_data = array();
while($row = $chart_result->fetch_assoc()) {
    $chart_data[] = $row;
}
$chart_query->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logbook - User's Project Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="sidebar.css" rel="stylesheet">
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

     <!-- N8N Chat Widget - Single Import -->
    <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            flex-direction: row;
            background: var(--body-color);
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            padding-bottom: 50px;
            min-height: 100vh;
            background-color: #f4f9f4;
            transition:  var(--tran-05);
            overflow-y: auto;
        }

        .sidebar.close ~ .main-content {
            margin-left: 88px;
        }

        .table {
            margin-top: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .main-content h1 {
            background: #2e7d32;
            padding: 10px;
            border-radius: 8px;
            color: #f4f9f4;
            box-shadow: 0 0 10px rgba(0,0,0,1);
            font-weight: 500;
        }

        .main-content h2 {
            color: #c8e6c9;;
            background-color: #1b5e20;
            width: fit-content;
            padding: 8px;
            border-radius: 6px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }

        .table thead th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        .header-section {
            display: flex;
            flex-direction: column;
            max-width: 150px;
            margin: 10px 0;
            gap: 10px;
        }

        .header-section a {
            text-decoration: none;
        }
        .header-section a .back-button {
            color: #333;
            gap: 5px;
            font-weight: 500;
        }
        .header-section i {
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .pagination {
            text-align: center;
            margin: 20px 0;
        }
        .pagination a {
            color: #333;
            text-decoration: none;
            padding: 8px 15px;
            display: inline-block;
            margin: 0 2px;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .pagination a.active {
            background-color: #2e7d32;
            color: white;
            font-weight: bold;
            border-color: #2e7d32;
        }

        .pagination a:hover:not(.active) {
            background-color: #c8e6c9; 
        }

        .pagination a.disabled {
            color: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination-info {
            text-align: center;
            color: #666;
            margin: 10px 0;
            font-size: 14px;
        }

        /* Statistics Section */
        .statistics-section {
            background: white;
            padding: 25px;
            margin: 30px 0 50px 0;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .statistics-section h2 {
            color: white;
            background-color: #1b5e20;
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2e7d32;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .stat-card .stat-label {
            color: #1b5e20;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .stat-card .stat-value {
            color: #2e7d32;
            font-size: 32px;
            font-weight: bold;
        }

        .stat-card .stat-unit {
            color: #558b2f;
            font-size: 14px;
            margin-left: 5px;
        }

        .stat-card.highlight {
            background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
        }

        .stat-card.highlight .stat-label,
        .stat-card.highlight .stat-value,
        .stat-card.highlight .stat-unit {
            color: white;
        }

        .stat-card .stat-subtext {
            color: #558b2f;
            font-size: 14px;
            margin-top: 8px;
            font-weight: 500;
        }

        .stat-card.highlight .stat-subtext {
            color: rgba(255,255,255,0.9);
        }

        /* Charts Section */
        .charts-section {
            margin-top: 30px;
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .chart-title {
            color: #1b5e20;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c8e6c9;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
        }

            /* Ensure chat widget is accessible and visible */
        #n8n-chat {
            z-index: 9999 !important;
        }
    
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="main-content" id="mainContent">

        <h1><?= $projectTitle ?> Entry</h1>

    <div class="container my-2">

        <div class="header-section">
        <a href="/FYP/logbookfirstpage.php"><i class='bx  bx-caret-left-square back-button'  ></i> </a>
        <a href="/FYP/create.php?projectID=<?php echo $projectID ?>" class='btn btn-success btn-sm'>Create Record</a>
        </div>

        <?php if ($total_records > 0): ?>
        <div class="pagination-info">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> records
        </div>
        <?php endif; ?>

        <table class="table">
        <thead>
            <tr>
                <th></th>
                <th>Record ID</th>
                <th>Name</th>
                <th>Date Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php

            $sql = $connect->prepare("SELECT recordId, recordName, recordDate FROM records WHERE projectID = ? ORDER BY recordDate DESC LIMIT ? OFFSET ?");
            $sql->bind_param("iii", $projectID, $records_per_page, $offset);
            $sql->execute();
            $result = $sql->get_result();

            if($result->num_rows === 0) {
                echo "<tr><td colspan='5' class='no-records'>No records found for this project.</td></tr>";
            }
            else {
                while($row = $result->fetch_assoc()) {
                    $recordId = $row['recordId'];
                    $recordName = htmlspecialchars($row['recordName']);
                    $recordDate = htmlspecialchars($row['recordDate']);

                    echo "<tr>
                        <td>
                            <a class='btn btn-info btn-sm' href='/FYP/viewRecord.php?recordId=$row[recordId]&projectID=$projectID'>View Record</a>
                        </td>
                        <td>" . $row["recordId"] . "</td>
                        <td>" . $row["recordName"] . "</td>
                        <td>" . $row["recordDate"] . "</td>
                        <td>
                            <a class='btn btn-primary btn-sm' href='/FYP/edit.php?recordId=$row[recordId]&projectID=$projectID'>Update</a>
                            <a class='btn btn-danger btn-sm' href='/FYP/delete.php?recordId=$row[recordId]&projectID=$projectID'>Delete</a>
                        </td>
                        </tr>";
                }
            }

            $sql->close();

            ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php
        // Previous button
        if ($current_page > 1) {
            echo '<a href="?projectID=' . $projectID . '&page=' . ($current_page - 1) . '">&laquo; Prev</a>';
        } else {
            echo '<a class="disabled">&laquo; Prev</a>';
        }

        // Page numbers
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);

        // Show first page if not in range
        if ($start_page > 1) {
            echo '<a href="?projectID=' . $projectID . '&page=1">1</a>';
            if ($start_page > 2) {
                echo '<a class="disabled">...</a>';
            }
        }

        // Show page numbers
        for ($i = $start_page; $i <= $end_page; $i++) {
            $active_class = ($i == $current_page) ? 'active' : '';
            echo '<a href="?projectID=' . $projectID . '&page=' . $i . '" class="' . $active_class . '">' . $i . '</a>';
        }

        // Show last page if not in range
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<a class="disabled">...</a>';
            }
            echo '<a href="?projectID=' . $projectID . '&page=' . $total_pages . '">' . $total_pages . '</a>';
        }

        // Next button
        if ($current_page < $total_pages) {
            echo '<a href="?projectID=' . $projectID . '&page=' . ($current_page + 1) . '">Next &raquo;</a>';
        } else {
            echo '<a class="disabled">Next &raquo;</a>';
        }
        ?>
    </div>
    <?php endif; ?>

    <!-- Statistics Section -->
    <div class="statistics-section">
        <h2>Project Statistics & Growth Analysis</h2>
        
        <!-- Summary Cards -->
        <div class="stats-grid">
            <div class="stat-card highlight">
                <div class="stat-label">Total Entries</div>
                <div class="stat-value"><?php echo number_format($stats['total_entries']); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Entries with Watering</div>
                <div class="stat-value">
                    <?php echo number_format($stats['watered_entries']); ?>
                    <span class="stat-unit">(<?php echo $stats['total_entries'] > 0 ? round(($stats['watered_entries'] / $stats['total_entries']) * 100) : 0; ?>%)</span>
                </div>
                <?php if ($most_common_water): ?>
                <div class="stat-subtext">Most common: <?php echo htmlspecialchars($most_common_water); ?></div>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <div class="stat-label">Entries with Fertilizer</div>
                <div class="stat-value">
                    <?php echo number_format($stats['fertilized_entries']); ?>
                    <span class="stat-unit">(<?php echo $stats['total_entries'] > 0 ? round(($stats['fertilized_entries'] / $stats['total_entries']) * 100) : 0; ?>%)</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Entries with Pesticide</div>
                <div class="stat-value">
                    <?php echo number_format($stats['pesticide_entries']); ?>
                    <span class="stat-unit">(<?php echo $stats['total_entries'] > 0 ? round(($stats['pesticide_entries'] / $stats['total_entries']) * 100) : 0; ?>%)</span>
                </div>
            </div>

            <?php if ($stats['avg_height'] !== null): ?>
            <div class="stat-card highlight">
                <div class="stat-label">Average Height</div>
                <div class="stat-value">
                    <?php echo number_format($stats['avg_height'], 2); ?>
                    <span class="stat-unit">cm</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($stats['avg_width'] !== null): ?>
            <div class="stat-card">
                <div class="stat-label">Average Width</div>
                <div class="stat-value">
                    <?php echo number_format($stats['avg_width'], 2); ?>
                    <span class="stat-unit">cm</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($stats['first_entry'] && $stats['latest_entry']): ?>
            <div class="stat-card highlight">
                <div class="stat-label">Project Duration</div>
                <div class="stat-value" style="font-size: 28px;">
                    <?php 
                    $diff = strtotime($stats['latest_entry']) - strtotime($stats['first_entry']);
                    $days = floor($diff / (60 * 60 * 24));
                    echo $days;
                    ?>
                    <span class="stat-unit">days</span>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Charts Section -->
        <?php if (count($chart_data) > 1): ?>
        <div class="charts-section">
            <div class="chart-container">
                <div class="chart-title">📈 Plant Height Growth Over Time</div>
                <div class="chart-wrapper">
                    <canvas id="heightChart"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">📏 Plant Width Growth Over Time</div>
                <div class="chart-wrapper">
                    <canvas id="widthChart"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">🌱 Height vs Width Comparison</div>
                <div class="chart-wrapper">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>

            <?php if (!empty($water_distribution)): ?>
            <div class="chart-container">
                <div class="chart-title">💧 Water Level Distribution</div>
                <div class="chart-wrapper">
                    <canvas id="waterChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <?php endif; ?>
    </div>

    </div>

    </section>

    <script>
        // Prepare chart data
        const chartData = <?php echo json_encode($chart_data); ?>;
        const waterDistribution = <?php echo json_encode($water_distribution); ?>;
        
        // Extract data for charts
        const dates = chartData.map(item => {
            const date = new Date(item.recordDate);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        
        const heights = chartData.map(item => item.height);
        const widths = chartData.map(item => item.width);

        // Common chart options
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        };

        // Height Chart
        if (document.getElementById('heightChart')) {
            new Chart(document.getElementById('heightChart'), {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Height (cm)',
                        data: heights,
                        borderColor: '#2e7d32',
                        backgroundColor: 'rgba(46, 125, 50, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#2e7d32',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: commonOptions
            });
        }

        // Width Chart
        if (document.getElementById('widthChart')) {
            new Chart(document.getElementById('widthChart'), {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Width (cm)',
                        data: widths,
                        borderColor: '#558b2f',
                        backgroundColor: 'rgba(85, 139, 47, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#558b2f',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: commonOptions
            });
        }

        // Water Distribution Chart (Pie Chart for string values)
        if (document.getElementById('waterChart') && waterDistribution.length > 0) {
            const waterLabels = waterDistribution.map(item => item.waterAmount);
            const waterCounts = waterDistribution.map(item => item.count);
            
            new Chart(document.getElementById('waterChart'), {
                type: 'pie',
                data: {
                    labels: waterLabels,
                    datasets: [{
                        label: 'Water Level Usage',
                        data: waterCounts,
                        backgroundColor: [
                            'rgba(33, 150, 243, 0.8)',
                            'rgba(3, 169, 244, 0.8)',
                            'rgba(100, 181, 246, 0.8)',
                            'rgba(144, 202, 249, 0.8)',
                            'rgba(187, 222, 251, 0.8)'
                        ],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} times (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Comparison Chart
        if (document.getElementById('comparisonChart')) {
            new Chart(document.getElementById('comparisonChart'), {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Height (cm)',
                            data: heights,
                            borderColor: '#2e7d32',
                            backgroundColor: 'rgba(46, 125, 50, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#2e7d32',
                            yAxisID: 'y'
                        },
                        {
                            label: 'Width (cm)',
                            data: widths,
                            borderColor: '#ff9800',
                            backgroundColor: 'rgba(255, 152, 0, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#ff9800',
                            yAxisID: 'y'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Size (cm)'
                            }
                        }
                    }
                }
            });
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

<?php
    $connect->close();
?>