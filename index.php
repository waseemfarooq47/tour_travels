
<?php
session_start();
require_once "config/database.php";

// Fetch popular destinations
$destinations = [];
$destinationQuery = $conn->query("
    SELECT id, name, location, description, image
    FROM destinations
    ORDER BY id DESC
    LIMIT 6
");

if ($destinationQuery) {
    while ($row = $destinationQuery->fetch_assoc()) {
        $destinations[] = $row;
    }
}

// Fetch featured tour packages
$packages = [];
$packageQuery = $conn->query("
    SELECT tp.id, tp.package_name, tp.duration, tp.price,
           tp.description,
           d.name AS destination_name,
           d.location AS destination_location
    FROM tour_packages tp
    INNER JOIN destinations d ON tp.destination_id = d.id
    ORDER BY tp.id DESC
    LIMIT 6
");

if ($packageQuery) {
    while ($row = $packageQuery->fetch_assoc()) {
        $packages[] = $row;
    }
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$loggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Explore Kashmir | Discover Your Next Journey</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #087e76;
    --dark: #102b2b;
    --light: #f5f8f6;
    --gold: #d9a441;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'DM Sans', sans-serif;
    color: #263b3a;
    background: #fff;
}

a {
    text-decoration: none;
}

.navbar {
    background: #fff;
    padding: 16px 0;
    box-shadow: 0 3px 18px rgba(0,0,0,.06);
}

.navbar-brand {
    font-weight: 800;
    font-size: 25px;
    color: var(--primary);
}

.nav-link {
    color: #263b3a;
    font-weight: 600;
    margin: 0 9px;
}

.nav-link:hover {
    color: var(--primary);
}

.btn-main {
    background: var(--primary);
    color: white;
    padding: 12px 25px;
    border-radius: 7px;
    font-weight: 700;
    border: none;
}

.btn-main:hover {
    background: #05645e;
    color: white;
}

.hero {
    min-height: 620px;
    background:
      linear-gradient(90deg, rgba(5,27,28,.82), rgba(5,27,28,.25)),
      url('https://images.unsplash.com/photo-1595815771614-ade9d652a65d?auto=format&fit=crop&w=2000&q=90')
      center/cover no-repeat;
    display: flex;
    align-items: center;
    color: white;
}

.hero-content {
    max-width: 700px;
    padding: 80px 0;
}

.hero-tag {
    color: #f3c66d;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    font-size: 13px;
}

.hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(42px, 6vw, 70px);
    line-height: 1.12;
    margin: 20px 0;
}

.hero p {
    font-size: 18px;
    line-height: 1.8;
    color: #e8eeee;
    max-width: 570px;
}

.search-panel {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 12px 40px rgba(0,0,0,.12);
    margin-top: -55px;
    position: relative;
    z-index: 2;
}

.search-panel label {
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 8px;
}

.search-panel .form-control,
.search-panel .form-select {
    min-height: 48px;
    border-color: #e4e9e7;
}

.section {
    padding: 85px 0;
}

.section-heading {
    text-align: center;
    margin-bottom: 45px;
}

.section-heading span {
    color: var(--primary);
    font-weight: 800;
    letter-spacing: 2px;
    font-size: 12px;
    text-transform: uppercase;
}

.section-heading h2 {
    font-family: 'Playfair Display', serif;
    font-size: 39px;
    color: var(--dark);
    margin-top: 12px;
}

.section-heading p {
    color: #71807d;
    max-width: 600px;
    margin: 12px auto 0;
}

.destination-card,
.package-card {
    border: none;
    border-radius: 13px;
    overflow: hidden;
    background: white;
    box-shadow: 0 5px 25px rgba(0,0,0,.07);
    height: 100%;
    transition: .3s;
}

.destination-card:hover,
.package-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 12px 30px rgba(0,0,0,.12);
}

.destination-card img {
    width: 100%;
    height: 245px;
    object-fit: cover;
}

.destination-card .card-body,
.package-card .card-body {
    padding: 22px;
}

.destination-card h5,
.package-card h5 {
    font-weight: 800;
    color: var(--dark);
}

.package-card img {
    width: 100%;
    height: 220px;
    object-fit: cover;
}

.package-meta {
    display: flex;
    justify-content: space-between;
    color: #74817e;
    font-size: 13px;
    margin: 15px 0;
}

.package-price {
    color: var(--primary);
    font-size: 23px;
    font-weight: 800;
}

.why-section {
    background: var(--light);
}

.feature-box {
    background: white;
    padding: 30px 22px;
    border-radius: 12px;
    height: 100%;
    text-align: center;
}

.feature-icon {
    width: 65px;
    height: 65px;
    background: #e2f2ef;
    color: var(--primary);
    display: grid;
    place-items: center;
    border-radius: 50%;
    font-size: 25px;
    margin: 0 auto 20px;
}

.feature-box h5 {
    font-weight: 800;
    color: var(--dark);
}

.gallery-img {
    width: 100%;
    height: 260px;
    object-fit: cover;
    border-radius: 12px;
    transition: .3s;
}

.gallery-img:hover {
    transform: scale(1.02);
}

.testimonial {
    background: var(--light);
    border-radius: 13px;
    padding: 30px;
    height: 100%;
}

.stars {
    color: var(--gold);
    margin-bottom: 15px;
}

.testimonial p {
    line-height: 1.8;
    color: #596966;
}

.cta {
    background:
      linear-gradient(rgba(5,35,35,.85),rgba(5,35,35,.85)),
      url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1800&q=85')
      center/cover;
    padding: 85px 20px;
    text-align: center;
    color: white;
}

.cta h2 {
    font-family: 'Playfair Display', serif;
    font-size: 42px;
}

footer {
    background: #102b2b;
    color: #c5d2cf;
    padding: 65px 0 20px;
}

footer h5 {
    color: white;
    margin-bottom: 20px;
    font-weight: 800;
}

footer a {
    color: #c5d2cf;
    display: block;
    margin-bottom: 11px;
}

footer a:hover {
    color: #f3c66d;
}

.footer-bottom {
    border-top: 1px solid #35504d;
    margin-top: 40px;
    padding-top: 20px;
    text-align: center;
    font-size: 13px;
}

.empty-state {
    background: var(--light);
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    color: #71807d;
}

@media(max-width: 768px) {
    .hero {
        min-height: 540px;
    }

    .hero-content {
        padding: 60px 15px;
    }

    .search-panel {
        margin-top: 20px;
    }

    .section {
        padding: 60px 0;
    }

    .section-heading h2 {
        font-size: 31px;
    }
}
</style>
</head>

<body>

<!-- NAVIGATION -->
<nav class="navbar navbar-expand-lg sticky-top">
<div class="container">

    <a class="navbar-brand" href="index.php">
        <i class="fa-solid fa-mountain-sun"></i> Explore Kashmir
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#mainNav">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">

            <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="#destinations">Destinations</a></li>
            <li class="nav-item"><a class="nav-link" href="#packages">Packages</a></li>
            <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
            <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>

            <?php if ($loggedIn): ?>
                <li class="nav-item">
                    <a class="btn btn-main ms-lg-3" href="customer/dashboard.php">
                        My Account
                    </a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="auth/login.php">Login</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-main ms-lg-2" href="auth/register.php">
                        Register
                    </a>
                </li>
            <?php endif; ?>

        </ul>
    </div>
</div>
</nav>

<!-- HERO -->
<section class="hero">
<div class="container">
    <div class="hero-content">
        <span class="hero-tag">Discover the beauty of Kashmir</span>

        <h1>Every Journey Begins With a Beautiful Destination.</h1>

        <p>
            Explore breathtaking valleys, peaceful lakes, snow-covered mountains
            and unforgettable experiences. Let us help you plan your perfect trip.
        </p>

        <a href="#packages" class="btn btn-main mt-3">
            Explore Tour Packages <i class="fa-solid fa-arrow-right ms-2"></i>
        </a>
    </div>
</div>
</section>

<!-- SEARCH -->
<div class="container">
<div class="search-panel">
    <form action="customer/packages.php" method="GET">
        <div class="row g-3 align-items-end">

            <div class="col-lg-4 col-md-6">
                <label>Where do you want to go?</label>
                <select name="destination" class="form-select">
                    <option value="">All Destinations</option>
                    <?php foreach ($destinations as $destination): ?>
                        <option value="<?= (int)$destination['id'] ?>">
                            <?= e($destination['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-3 col-md-6">
                <label>Travel Date</label>
                <input type="date" name="travel_date"
                       class="form-control" min="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-lg-2 col-md-6">
                <label>Travellers</label>
                <input type="number" name="persons" class="form-control"
                       min="1" value="2">
            </div>

            <div class="col-lg-3 col-md-6">
                <button type="submit" class="btn btn-main w-100">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>
                    Search Trips
                </button>
            </div>

        </div>
    </form>
</div>
</div>

<!-- DESTINATIONS -->
<section class="section" id="destinations">
<div class="container">

    <div class="section-heading">
        <span>Explore the beauty</span>
        <h2>Popular Destinations</h2>
        <p>Discover some of the most beautiful places waiting to be explored.</p>
    </div>

    <div class="row g-4">

        <?php if (count($destinations) > 0): ?>
            <?php foreach ($destinations as $destination): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="destination-card">

                        <img
                            src="<?= e(!empty($destination['image'])
                                ? $destination['image']
                                : 'https://images.unsplash.com/photo-1470770841072-f978cf4d019e?w=900') ?>"
                            alt="<?= e($destination['name']) ?>">

                        <div class="card-body">
                            <h5><?= e($destination['name']) ?></h5>

                            <p class="text-muted small">
                                <i class="fa-solid fa-location-dot text-success"></i>
                                <?= e($destination['location']) ?>
                            </p>

                            <p class="text-muted">
                                <?= e(substr($destination['description'] ?? '', 0, 105)) ?>
                            </p>

                            <a href="customer/packages.php?destination=<?= (int)$destination['id'] ?>"
                               class="fw-bold" style="color:var(--primary)">
                                Explore Destination
                                <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state">Destinations will be available soon.</div>
            </div>
        <?php endif; ?>

    </div>
</div>
</section>

<!-- PACKAGES -->
<section class="section why-section" id="packages">
<div class="container">

    <div class="section-heading">
        <span>Handpicked experiences</span>
        <h2>Featured Tour Packages</h2>
        <p>Choose from our specially selected travel experiences.</p>
    </div>

    <div class="row g-4">

        <?php if (count($packages) > 0): ?>
            <?php foreach ($packages as $package): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="package-card">

                        <img
                            src="<?= e(!empty($package['image'])
                                ? $package['image']
                                : 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=900') ?>"
                            alt="<?= e($package['package_name']) ?>">

                        <div class="card-body">

                            <div class="package-meta">
                                <span>
                                    <i class="fa-solid fa-location-dot"></i>
                                    <?= e($package['destination_name']) ?>
                                </span>

                                <span>
                                    <i class="fa-regular fa-clock"></i>
                                    <?= e($package['duration']) ?>
                                </span>
                            </div>

                            <h5><?= e($package['package_name']) ?></h5>

                            <p class="text-muted">
                                <?= e(substr($package['description'] ?? '', 0, 100)) ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <small class="text-muted">Starting from</small>
                                    <div class="package-price">
                                        ₹<?= number_format((float)$package['price'], 2) ?>
                                    </div>
                                </div>

                                <a href="customer/package_details.php?id=<?= (int)$package['id'] ?>"
                                   class="btn btn-main">
                                    View Details
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state">Tour packages will be available soon.</div>
            </div>
        <?php endif; ?>

    </div>

    <div class="text-center mt-5">
        <a href="customer/packages.php" class="btn btn-main">
            View All Packages
        </a>
    </div>

</div>
</section>

<!-- ABOUT / WHY CHOOSE US -->
<section class="section" id="about">
<div class="container">

    <div class="section-heading">
        <span>Your journey, our responsibility</span>
        <h2>Why Travel With Us?</h2>
        <p>We aim to make every journey comfortable, memorable and hassle-free.</p>
    </div>

    <div class="row g-4">

        <div class="col-lg-3 col-md-6">
            <div class="feature-box">
                <div class="feature-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                <h5>Local Expertise</h5>
                <p class="text-muted">Explore destinations with helpful local travel knowledge.</p>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="feature-box">
                <div class="feature-icon"><i class="fa-solid fa-wallet"></i></div>
                <h5>Affordable Packages</h5>
                <p class="text-muted">Enjoy thoughtfully planned journeys at competitive prices.</p>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="feature-box">
                <div class="feature-icon"><i class="fa-solid fa-headset"></i></div>
                <h5>Travel Assistance</h5>
                <p class="text-muted">Get assistance throughout your booking and travel planning.</p>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="feature-box">
                <div class="feature-icon"><i class="fa-solid fa-heart"></i></div>
                <h5>Memorable Experiences</h5>
                <p class="text-muted">Create wonderful memories with carefully planned trips.</p>
            </div>
        </div>

    </div>
</div>
</section>

<!-- GALLERY -->
<section class="section why-section">
<div class="container">

    <div class="section-heading">
        <span>Moments worth remembering</span>
        <h2>Explore Through Our Gallery</h2>
        <p>A glimpse of the landscapes and experiences that make Kashmir special.</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-4 col-md-6">
            <img class="gallery-img"
                 src="https://images.unsplash.com/photo-1566837497312-7be4a9e9a8c5?w=900"
                 alt="Kashmir valley">
        </div>

        <div class="col-lg-4 col-md-6">
            <img class="gallery-img"
                 src="https://images.unsplash.com/photo-1500534623283-312aade485b7?w=900"
                 alt="Mountain landscape">
        </div>

        <div class="col-lg-4 col-md-6">
            <img class="gallery-img"
                 src="https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=900"
                 alt="Beautiful lake">
        </div>

        <div class="col-lg-4 col-md-6">
            <img class="gallery-img"
                 src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=900"
                 alt="Snow covered mountains">
        </div>

        <div class="col-lg-4 col-md-6">
            <img class="gallery-img"
                 src="https://images.unsplash.com/photo-1470770841072-f978cf4d019e?w=900"
                 alt="Mountain lake">
        </div>

        <div class="col-lg-4 col-md-6">
            <img class="gallery-img"
                 src="https://images.unsplash.com/photo-1472396961693-142e6e269027?w=900"
                 alt="Nature landscape">
        </div>
    </div>

</div>
</section>

<!-- TESTIMONIALS -->
<section class="section" id="testimonials">
<div class="container">

    <div class="section-heading">
        <span>Guest experiences</span>
        <h2>What Our Travellers Say</h2>
        <p>Every journey has a story. Here is what travellers have to say.</p>
    </div>

    <div class="row g-4">

        <div class="col-lg-4">
            <div class="testimonial">
                <div class="stars">★★★★★</div>
                <p>
                    "The beautiful destinations and peaceful surroundings made
                    our Kashmir trip unforgettable. The experience was wonderful."
                </p>
                <h6 class="fw-bold mb-1">Happy Traveller</h6>
                <small class="text-muted">Family Trip</small>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="testimonial">
                <div class="stars">★★★★★</div>
                <p>
                    "The booking process was simple and the trip planning was
                    convenient. We enjoyed every moment of our holiday."
                </p>
                <h6 class="fw-bold mb-1">Satisfied Guest</h6>
                <small class="text-muted">Holiday Experience</small>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="testimonial">
                <div class="stars">★★★★★</div>
                <p>
                    "A memorable travel experience with breathtaking views.
                    Kashmir truly feels like paradise on earth."
                </p>
                <h6 class="fw-bold mb-1">Travel Enthusiast</h6>
                <small class="text-muted">Leisure Trip</small>
            </div>
        </div>

    </div>
</div>
</section>

<!-- CALL TO ACTION -->
<section class="cta">
    <div class="container">
        <h2>Ready to Explore Something Beautiful?</h2>
        <p class="mt-3 mb-4">
            Your next unforgettable journey is just a few clicks away.
        </p>

        <a href="customer/packages.php" class="btn btn-main">
            Plan Your Journey <i class="fa-solid fa-arrow-right ms-2"></i>
        </a>
    </div>
</section>

<!-- FOOTER -->
<footer id="contact">
<div class="container">
    <div class="row g-4">

        <div class="col-lg-4">
            <h5><i class="fa-solid fa-mountain-sun"></i> Explore Kashmir</h5>
            <p>
                Discover beautiful destinations, explore memorable experiences
                and plan your next journey with ease.
            </p>
        </div>

        <div class="col-lg-2 col-md-4">
            <h5>Quick Links</h5>
            <a href="index.php">Home</a>
            <a href="#destinations">Destinations</a>
            <a href="#packages">Packages</a>
            <a href="#about">About Us</a>
        </div>

        <div class="col-lg-3 col-md-4">
            <h5>Customer Support</h5>
            <a href="auth/login.php">Login</a>
            <a href="auth/register.php">Register</a>
            <a href="customer/bookings.php">My Bookings</a>
            <a href="#contact">Contact Us</a>
        </div>

        <div class="col-lg-3 col-md-4">
            <h5>Get In Touch</h5>
            <p><i class="fa-solid fa-location-dot me-2"></i> Kashmir, India</p>
            <p><i class="fa-solid fa-envelope me-2"></i> Contact us by email</p>
            <div class="d-flex gap-3 mt-3">
                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook fa-lg"></i></a>
                <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram fa-lg"></i></a>
                <a href="#" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp fa-lg"></i></a>
            </div>
        </div>

    </div>

    <div class="footer-bottom">
        &copy; <?= date('Y') ?> Explore Kashmir. All Rights Reserved.
    </div>
</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
