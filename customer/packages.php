<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/database.php";

/*
    Get active tour packages
    with active destinations
*/

$query = "
    SELECT
        tour_packages.id,
        tour_packages.package_name,
        tour_packages.description,
        tour_packages.duration,
        tour_packages.price,
        destinations.name AS destination_name,
        destinations.location AS destination_location,
        destinations.image AS destination_image
    FROM tour_packages

    INNER JOIN destinations
        ON tour_packages.destination_id = destinations.id

    WHERE tour_packages.status = 1
    AND destinations.status = 1

    ORDER BY tour_packages.id DESC
";

$packages = $conn->query($query);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tour Packages | Tour & Travel</title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }


        /* =========================
           PAGE HEADER
        ========================= */

        .page-header {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
        }


        .page-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }


        .page-header p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
        }


        /* =========================
           PACKAGE GRID
        ========================= */

        .package-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }


        /* =========================
           PACKAGE CARD
        ========================= */

        .package-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;

            transition: transform 0.2s, box-shadow 0.2s;
        }


        .package-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }


        /* =========================
           IMAGE
        ========================= */

        .package-image {
            height: 190px;
            background: #e5e7eb;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;
        }


        .package-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        .no-image {
            color: #9ca3af;
            font-size: 14px;
        }


        /* =========================
           CARD CONTENT
        ========================= */

        .package-content {
            padding: 22px;
        }


        .package-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 8px;
        }


        .destination {
            color: #2563eb;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 15px;
        }


        .description {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.6;
            min-height: 65px;
            margin-bottom: 18px;
        }


        /* =========================
           PACKAGE DETAILS
        ========================= */

        .details {
            display: flex;
            justify-content: space-between;
            align-items: center;

            border-top: 1px solid #f0f0f0;
            padding-top: 16px;
            margin-bottom: 18px;
        }


        .duration {
            font-size: 13px;
            color: #6b7280;
        }


        .price {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
        }


        .price-label {
            font-size: 11px;
            font-weight: normal;
            color: #9ca3af;
        }


        /* =========================
           BOOK BUTTON
        ========================= */

        .book-button {
            display: block;
            width: 100%;

            background: #2563eb;
            color: white;

            text-align: center;
            text-decoration: none;

            padding: 11px;
            border-radius: 7px;

            font-size: 14px;
            font-weight: bold;
        }


        .book-button:hover {
            background: #1d4ed8;
        }


        /* =========================
           EMPTY STATE
        ========================= */

        .empty {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;

            text-align: center;
            padding: 60px 20px;
        }


        .empty h2 {
            margin-bottom: 10px;
        }


        .empty p {
            color: #6b7280;
            font-size: 14px;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1000px) {

            .package-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }


        @media (max-width: 650px) {

            .package-grid {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 23px;
            }

        }

    </style>

</head>


<body>


<?php require_once "../includes/customer_header.php"; ?>


<!-- =========================
     PAGE CONTENT
========================= -->

<div class="customer-container">


    <div class="page-header">

        <h1>
            Explore Tour Packages
        </h1>

        <p>
            Discover beautiful destinations and choose a tour package
            that suits your travel plans.
        </p>

    </div>


    <?php if ($packages && $packages->num_rows > 0): ?>


        <div class="package-grid">


            <?php while ($package = $packages->fetch_assoc()): ?>


                <div class="package-card">


                    <!-- IMAGE -->

                    <div class="package-image">

                        <?php if (!empty($package['destination_image'])): ?>

                            <img
                                src="../<?php echo htmlspecialchars($package['destination_image']); ?>"
                                alt="<?php echo htmlspecialchars($package['package_name']); ?>"
                            >

                        <?php else: ?>

                            <div class="no-image">
                                No image available
                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- CONTENT -->

                    <div class="package-content">


                        <div class="package-title">

                            <?php
                            echo htmlspecialchars($package['package_name']);
                            ?>

                        </div>


                        <div class="destination">

                            📍
                            <?php
                            echo htmlspecialchars($package['destination_name']);
                            ?>

                            -

                            <?php
                            echo htmlspecialchars($package['destination_location']);
                            ?>

                        </div>


                        <div class="description">

                            <?php

                            if (!empty($package['description'])) {

                                echo htmlspecialchars($package['description']);

                            } else {

                                echo "Explore this destination with our carefully planned tour package.";

                            }

                            ?>

                        </div>


                        <!-- DETAILS -->

                        <div class="details">


                            <div class="duration">

                                Duration:

                                <strong>
                                    <?php echo $package['duration']; ?>
                                </strong>

                                day(s)

                            </div>


                            <div class="price">

                                ₹<?php
                                echo number_format($package['price'], 2);
                                ?>

                                <div class="price-label">
                                    per person
                                </div>

                            </div>


                        </div>


                        <!-- BOOK -->

                        <a
                            href="book.php?id=<?php echo $package['id']; ?>"
                            class="book-button"
                        >

                            Book Now

                        </a>


                    </div>

                </div>


            <?php endwhile; ?>


        </div>


    <?php else: ?>


        <div class="empty">

            <h2>
                No Tour Packages Available
            </h2>

            <p>
                There are currently no active tour packages.
                Please check again later.
            </p>

        </div>


    <?php endif; ?>


</div>


<?php require_once "../includes/customer_footer.php"; ?>


</body>

</html>