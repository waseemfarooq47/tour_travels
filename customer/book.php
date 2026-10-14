
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";

// ==========================================
// 1. CUSTOMER AUTHENTICATION
// ==========================================
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "customer"
) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

// ==========================================
// 2. VALIDATE PACKAGE ID
// ==========================================
if (
    !isset($_GET["id"]) ||
    !ctype_digit((string) $_GET["id"]) ||
    (int) $_GET["id"] < 1
) {
    header("Location: packages.php");
    exit();
}

$package_id = (int) $_GET["id"];

// ==========================================
// 3. FETCH PACKAGE DETAILS
// ==========================================
$stmt = $conn->prepare("
    SELECT
        tp.id,
        tp.package_name,
        tp.description,
        tp.duration,
        tp.price,
        d.name AS destination_name,
        d.location AS destination_location,
        d.image AS destination_image
    FROM tour_packages tp
    INNER JOIN destinations d ON tp.destination_id = d.id
    WHERE tp.id = ?
      AND tp.status = 1
      AND d.status = 1
    LIMIT 1
");

if (!$stmt) {
    die("Unable to load the tour package. Please try again later.");
}

$stmt->bind_param("i", $package_id);
$stmt->execute();

$result = $stmt->get_result();
$package = $result->fetch_assoc();
$stmt->close();

if (!$package) {
    // No header has been output yet, so it is safe to include it now.
    require_once "../includes/customer_header.php";
    ?>
    <div class="booking-error-page">
        <h2>Tour Package Not Found</h2>
        <p>This package is unavailable or may have been removed.</p>
        <a href="packages.php">Back to Tour Packages</a>
    </div>
    <?php
    require_once "../includes/customer_footer.php";
    exit();
}

// ==========================================
// 4. PROCESS BOOKING BEFORE HTML OUTPUT
// ==========================================
$error = "";

$travel_date = "";
$persons = 1;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $travel_date = trim($_POST["travel_date"] ?? "");
    $personsInput = $_POST["persons"] ?? "";

    $persons = filter_var(
        $personsInput,
        FILTER_VALIDATE_INT
    );

    // Validate date format exactly
    $dateObject = DateTime::createFromFormat("!Y-m-d", $travel_date);
    $validDate = $dateObject &&
        $dateObject->format("Y-m-d") === $travel_date;

    if (!$validDate) {
        $error = "Please select a valid travel date.";

    } elseif ($travel_date < date("Y-m-d")) {
        $error = "Travel date cannot be in the past.";

    } elseif ($persons === false || $persons < 1 || $persons > 100) {
        $error = "Please enter between 1 and 100 persons.";

    } else {

        $price = (float) $package["price"];
        $total_amount = round($price * $persons, 2);

        $insert = $conn->prepare("
            INSERT INTO bookings
                (user_id, package_id, travel_date, persons,
                 total_amount, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");

        if (!$insert) {
            $error = "Unable to prepare your booking. Please try again.";

        } else {

            $insert->bind_param(
                "iisid",
                $user_id,
                $package_id,
                $travel_date,
                $persons,
                $total_amount
            );

            if ($insert->execute()) {

                $booking_id = $insert->insert_id;
                $insert->close();

                // Redirect BEFORE including any HTML header
                header("Location: payment.php?id=" . $booking_id);
                exit();

            } else {
                $error = "Unable to create booking. Please try again.";
                $insert->close();
            }
        }
    }
}

// ==========================================
// 5. INCLUDE CUSTOMER HEADER ONLY ONCE
// ==========================================
require_once "../includes/customer_header.php";

$packagePrice = (float) $package["price"];
$initialPersons = isset($_POST["persons"])
    ? max(1, min(100, (int) $_POST["persons"]))
    : 1;

$initialTotal = $packagePrice * $initialPersons;

$imagePath = trim($package["destination_image"] ?? "");

if ($imagePath !== "") {
    $imagePath = "../" . ltrim($imagePath, "/");
}
?>

<style>
.booking-page {
    max-width: 1100px;
    margin: 0 auto;
    padding-bottom: 30px;
}

.booking-heading {
    margin-bottom: 25px;
}

.booking-heading h1 {
    font-size: 30px;
    margin-bottom: 8px;
    color: #111827;
    font-weight: 800;
}

.booking-heading p {
    color: #6b7280;
    font-size: 14px;
}

.booking-breadcrumb {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin-bottom: 22px;
    color: #64748b;
    font-size: 13px;
}

.booking-breadcrumb a {
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
}

.booking-layout {
    display: grid;
    grid-template-columns: 1.2fr .8fr;
    align-items: start;
    gap: 25px;
}

.package-summary,
.booking-form {
    overflow: hidden;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(15, 23, 42, .045);
}

.package-image {
    height: 260px;
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

.package-info {
    padding: 25px;
}

.package-label {
    display: inline-block;
    padding: 6px 10px;
    margin-bottom: 12px;
    background: #eff6ff;
    color: #1d4ed8;
    border-radius: 30px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .6px;
    text-transform: uppercase;
}

.package-info h2 {
    font-size: 23px;
    line-height: 1.4;
    margin-bottom: 10px;
    color: #111827;
}

.destination {
    color: #2563eb;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 18px;
}

.description {
    color: #6b7280;
    font-size: 13px;
    line-height: 1.8;
    overflow-wrap: anywhere;
    margin-bottom: 20px;
}

.package-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    padding-top: 18px;
    border-top: 1px solid #e5e7eb;
}

.detail-item {
    font-size: 12px;
    color: #6b7280;
}

.detail-item strong {
    display: block;
    color: #111827;
    font-size: 18px;
    margin-top: 5px;
    overflow-wrap: anywhere;
}

.booking-form {
    padding: 25px;
}

.booking-form h2 {
    font-size: 21px;
    margin-bottom: 8px;
    color: #111827;
}

.form-intro {
    color: #6b7280;
    font-size: 12px;
    margin-bottom: 23px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 8px;
}

.form-group input {
    width: 100%;
    min-height: 46px;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    outline: none;
    background: white;
}

.form-group input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
}

.form-hint {
    display: block;
    margin-top: 6px;
    color: #94a3b8;
    font-size: 11px;
}

.total-box {
    background: #f3f6ff;
    border: 1px solid #dbe4ff;
    border-radius: 10px;
    padding: 18px;
    margin: 25px 0 20px;
}

.total-label {
    font-size: 13px;
    color: #6b7280;
}

.total-amount {
    font-size: 27px;
    font-weight: 800;
    color: #1d4ed8;
    margin-top: 5px;
    overflow-wrap: anywhere;
}

.payment-note {
    font-size: 11px;
    color: #6b7280;
    margin-top: 7px;
    line-height: 1.6;
}

.book-button {
    width: 100%;
    border: none;
    background: #2563eb;
    color: white;
    padding: 14px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    transition: background .2s;
}

.book-button:hover {
    background: #1d4ed8;
}

.back-link {
    display: block;
    margin-top: 17px;
    color: #2563eb;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    text-align: center;
}

.back-link:hover {
    text-decoration: underline;
}

.error-message {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
    padding: 13px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 13px;
}

.booking-error-page {
    max-width: 800px;
    margin: 30px auto;
    padding: 25px;
    border-radius: 12px;
    background: white;
    text-align: center;
}

.booking-error-page a {
    display: inline-block;
    margin-top: 15px;
    color: #2563eb;
}

@media (max-width: 800px) {
    .booking-layout {
        grid-template-columns: 1fr;
    }

    .package-image {
        height: 230px;
    }
}

@media (max-width: 500px) {
    .booking-heading h1 {
        font-size: 25px;
    }

    .package-info,
    .booking-form {
        padding: 20px;
    }

    .package-details {
        gap: 10px;
    }

    .detail-item strong {
        font-size: 16px;
    }
}
</style>

<main class="booking-page">

    <div class="booking-breadcrumb">
        <a href="dashboard.php">Dashboard</a>
        <span>›</span>
        <a href="packages.php">Tour Packages</a>
        <span>›</span>
        <span>Book Your Tour</span>
    </div>

    <div class="booking-heading">
        <h1>Book Your Tour</h1>
        <p>Review your selected package and provide your travel details.</p>
    </div>

    <?php if ($error !== ""): ?>
        <div class="error-message" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <div class="booking-layout">

        <!-- PACKAGE SUMMARY -->
        <section class="package-summary">

            <div class="package-image">
                <?php if ($imagePath !== ""): ?>
                    <img
                        src="<?= htmlspecialchars($imagePath, ENT_QUOTES, "UTF-8") ?>"
                        alt="<?= htmlspecialchars($package["package_name"], ENT_QUOTES, "UTF-8") ?>"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                    >
                    <div class="no-image" style="display:none;">
                        Image unavailable
                    </div>
                <?php else: ?>
                    <div class="no-image">No image available</div>
                <?php endif; ?>
            </div>

            <div class="package-info">

                <span class="package-label">Selected Tour Package</span>

                <h2>
                    <?= htmlspecialchars($package["package_name"], ENT_QUOTES, "UTF-8") ?>
                </h2>

                <div class="destination">
                    📍
                    <?= htmlspecialchars($package["destination_name"], ENT_QUOTES, "UTF-8") ?>
                    <?php if (!empty($package["destination_location"])): ?>
                        — <?= htmlspecialchars($package["destination_location"], ENT_QUOTES, "UTF-8") ?>
                    <?php endif; ?>
                </div>

                <div class="description">
                    <?php if (!empty($package["description"])): ?>
                        <?= nl2br(htmlspecialchars($package["description"], ENT_QUOTES, "UTF-8")) ?>
                    <?php else: ?>
                        Explore this destination with our carefully planned tour package.
                    <?php endif; ?>
                </div>

                <div class="package-details">

                    <div class="detail-item">
                        Duration
                        <strong>
                            <?= htmlspecialchars((string) $package["duration"], ENT_QUOTES, "UTF-8") ?>
                            <?= is_numeric($package["duration"])
                                ? ((float) $package["duration"] === 1.0 ? "Day" : "Days")
                                : "" ?>
                        </strong>
                    </div>

                    <div class="detail-item">
                        Price Per Person
                        <strong>
                            ₹<?= number_format($packagePrice, 2) ?>
                        </strong>
                    </div>

                </div>
            </div>
        </section>

        <!-- BOOKING FORM -->
        <section class="booking-form">

            <h2>Travel Details</h2>
            <p class="form-intro">
                Choose your travel date and number of travellers.
            </p>

            <form method="POST" action="book.php?id=<?= $package_id ?>">

                <div class="form-group">
                    <label for="travel_date">Travel Date</label>

                    <input
                        type="date"
                        id="travel_date"
                        name="travel_date"
                        min="<?= date("Y-m-d") ?>"
                        value="<?= htmlspecialchars($travel_date, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >

                    <span class="form-hint">
                        Select today or a future date.
                    </span>
                </div>

                <div class="form-group">
                    <label for="persons">Number of Persons</label>

                    <input
                        type="number"
                        id="persons"
                        name="persons"
                        min="1"
                        max="100"
                        value="<?= $initialPersons ?>"
                        required
                    >

                    <span class="form-hint">
                        Maximum 100 travellers per booking.
                    </span>
                </div>

                <div class="total-box">
                    <div class="total-label">Estimated Total</div>

                    <div class="total-amount">
                        ₹<span id="totalAmount"><?= number_format($initialTotal, 2, ".", "") ?></span>
                    </div>

                    <div class="payment-note">
                        Total = price per person × number of persons.
                    </div>
                </div>

                <button type="submit" class="book-button">
                    Proceed to Payment →
                </button>

                <a href="packages.php" class="back-link">
                    ← Back to Tour Packages
                </a>

            </form>
        </section>

    </div>
</main>

<script>
const personsInput = document.getElementById("persons");
const totalAmount = document.getElementById("totalAmount");
const pricePerPerson = <?= json_encode($packagePrice) ?>;

function updateBookingTotal() {
    let persons = parseInt(personsInput.value, 10);

    if (!Number.isFinite(persons) || persons < 1) {
        persons = 1;
    }

    if (persons > 100) {
        persons = 100;
    }

    totalAmount.textContent = (pricePerPerson * persons).toLocaleString(
        "en-IN",
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );
}

personsInput.addEventListener("input", updateBookingTotal);
updateBookingTotal();
</script>

<?php
require_once "../includes/customer_footer.php";
?>
