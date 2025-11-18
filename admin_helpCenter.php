<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is admin or a superadmin
if (!isset($_SESSION['userId']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $inquiry_id = intval($_POST['inquiry_id']);
    $new_status = $_POST['status'];
    
    $stmt = $connect->prepare("UPDATE inquiries SET status = ? WHERE inquiryId = ?");
    $stmt->bind_param("si", $new_status, $inquiry_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin_helpCenter.php");
    exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_inquiry'])) {
    $inquiry_id = intval($_POST['inquiry_id']);
    
    $stmt = $connect->prepare("DELETE FROM inquiries WHERE inquiryId = ?");
    $stmt->bind_param("i", $inquiry_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin_helpCenter.php");
    exit;
}

// Fetch inquiries with user email using JOIN
$filter_status = $_GET['filter'] ?? 'all';

if ($filter_status === 'all') {
    $query = "SELECT i.*, u.email as user_email, u.username 
              FROM inquiries i 
              LEFT JOIN users u ON i.userId = u.userId 
              ORDER BY i.created_at DESC";
    $stmt = $connect->prepare($query);
} else {
    $query = "SELECT i.*, u.email as user_email, u.username 
              FROM inquiries i 
              LEFT JOIN users u ON i.userId = u.userId 
              WHERE i.status = ? 
              ORDER BY i.created_at DESC";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("s", $filter_status);
}

$stmt->execute();
$result = $stmt->get_result();
$inquiries = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM inquiries";
$stats_result = $connect->query($stats_query);
$stats = $stats_result->fetch_assoc();
$connect->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Help Center</title>
    <link rel="stylesheet" href="sidebar.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
        display: flex;
        min-height: 100vh;
        flex-direction: row;
        background: #f4f9f4;
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
        
        .header {
            background: white;
            color: #2e7d32;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 1.5rem;
        }
        
        .header .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.8em;
        }
        
        .header a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            transition: background 0.3s;
        }
        
        .header a:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #999;
            font-size: 0.9em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #2e7d32;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filters span {
            font-weight: 600;
            color: #2e7d32;
        }
        
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #2e7d32;
            background: white;
            color: #2e7d32;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: #2e7d32;
            color: white;
        }
        
        .inquiries-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #2e7d32;
            color: white;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        td {
            padding: 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-in_progress {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-resolved {
            background: #d4edda;
            color: #155724;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.2s;
        }
        
        .btn-view {
            background: #2e7d32;
            color: white;
        }
        
        .btn-view:hover {
            background: #3f8142ff;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        select {
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .modal-header h2 {
            color: #2e7d32;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: #999;
        }
        
        .close-btn:hover {
            color: #333;
        }
        
        .detail-row {
            margin-bottom: 20px;
        }
        
        .detail-row label {
            display: block;
            font-weight: 600;
            color: #2e7d32;
            margin-bottom: 8px;
        }
        
        .detail-row p {
            color: #555;
            line-height: 1.6;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .stats {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.9em;
            }
            
            th, td {
                padding: 10px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
        .header .container i {
            margin-top: 10px;
            font-size: 35px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="main-content">

    <div class="header">
        <div class="container">
            <h1><i class='bx bx-help-circle'></i> Inquiry Management</h1>
        </div>
    </div>
    
    <div class="container">
        <div class="stats">
            <div class="stat-card">
                <h3>Total Inquiries</h3>
                <div class="number"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending</h3>
                <div class="number"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="stat-card">
                <h3>In Progress</h3>
                <div class="number"><?php echo $stats['in_progress']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Resolved</h3>
                <div class="number"><?php echo $stats['resolved']; ?></div>
            </div>
        </div>
        
        <div class="filters">
            <span>Filter by Status:</span>
            <a href="admin_helpCenter.php?filter=all" class="filter-btn <?php echo $filter_status === 'all' ? 'active' : ''; ?>">All</a>
            <a href="admin_helpCenter.php?filter=pending" class="filter-btn <?php echo $filter_status === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="admin_helpCenter.php?filter=in_progress" class="filter-btn <?php echo $filter_status === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
            <a href="admin_helpCenter.php?filter=resolved" class="filter-btn <?php echo $filter_status === 'resolved' ? 'selected' : ''; ?>">Resolved</a>
        </div>
        
        <div class="inquiries-table">
            <?php if (count($inquiries) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Submitted by</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inquiries as $inquiry): ?>
                    <tr>
                        <td>#<?php echo $inquiry['inquiryId']; ?></td>
                        <td><?php echo htmlspecialchars(substr($inquiry['subject'], 0, 50)) . (strlen($inquiry['subject']) > 50 ? '...' : ''); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="inquiry_id" value="<?php echo $inquiry['inquiryId']; ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="pending" <?php echo $inquiry['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_progress" <?php echo $inquiry['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="resolved" <?php echo $inquiry['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </td>
                        <td><?php echo htmlspecialchars($inquiry['user_email'] ?? 'Unknown User'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?></td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-view" onclick="viewInquiry(<?php echo htmlspecialchars(json_encode($inquiry)); ?>)">View</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this inquiry?');">
                                    <input type="hidden" name="inquiry_id" value="<?php echo $inquiry['inquiryId']; ?>">
                                    <input type="hidden" name="delete_inquiry" value="1">
                                    <button type="submit" class="btn btn-delete">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <h3>No inquiries found</h3>
                <p>There are no inquiries matching your current filter.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal" id="inquiryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Inquiry Details</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalBody"></div>
        </div>
    </div>

    </section>

    
    <script>
        function viewInquiry(inquiry) {
            const modal = document.getElementById('inquiryModal');
            const modalBody = document.getElementById('modalBody');
            
            const statusClass = 'status-' + inquiry.status;
            const statusText = inquiry.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            
            modalBody.innerHTML = `
                <div class="detail-row">
                    <label>Inquiry ID:</label>
                    <p>#${inquiry.inquiryId}</p>
                </div>
                <div class="detail-row">
                    <label>Subject:</label>
                    <p>${inquiry.subject}</p>
                </div>
                <div class="detail-row">
                    <label>Message:</label>
                    <p style="white-space: pre-wrap;">${inquiry.message}</p>
                </div>
                <div class="detail-row">
                    <label>Submitted by:</label>
                    <p>${inquiry.username || 'Unknown'} (${inquiry.user_email || 'N/A'})</p>
                </div>
                <div class="detail-row">
                    <label>Status:</label>
                    <p><span class="status-badge ${statusClass}">${statusText}</span></p>
                </div>
                <div class="detail-row">
                    <label>Submitted:</label>
                    <p>${new Date(inquiry.created_at).toLocaleString()}</p>
                </div>
                <div class="detail-row">
                    <label>Last Updated:</label>
                    <p>${new Date(inquiry.updated_at).toLocaleString()}</p>
                </div>
            `;
            
            modal.classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('inquiryModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('inquiryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>