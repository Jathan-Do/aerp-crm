<?php
if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($title); ?></title>
    <?php wp_head(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-sidebar {
            min-height: 100vh;
            background: #2c3e50;
            color: white;
        }

        .dashboard-content {
            padding: 20px;
        }

        .nav-link {
            color: white;
            padding: 10px 20px;
        }

        .nav-link:hover {
            background: #34495e;
            color: white;
        }

        .nav-link.active {
            background: #3498db;
        }

        .category-card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: box-shadow 0.2s;
        }

        .category-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .category-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include(AERP_CRM_PATH . 'frontend/dashboard/sidebar.php'); ?>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 dashboard-content">
                <?php echo $content; ?>
            </div>
        </div>
    </div>
    <?php wp_footer(); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html> 