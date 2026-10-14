
<?php
session_start();

// Customer authentication
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "customer"
) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/database.php";

// Fetch active packages with active destinations
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

if (!$packages) {
    die("Unable to load tour packages. Please try again later.");
}

$packageList = [];
$destinationsList = [];

while ($package = $packages->fetch_assoc()) {
    $packageList[] = $package;

    $destination = trim($package["destination_name"] ?? "");

    if ($destination !== "") {
        $destinationsList[$destination] = $destination;
    }
}

ksort($destinationsList, SORT_NATURAL | SORT_FLAG_CASE);

require_once "../includes/customer_header.php";
?>

<style>
/* ==========================
   CUSTOMER TOUR PACKAGE PAGE
========================== */

.tour-page,
.tour-page * {
    box-sizing: border-box;
}

.tour-page {
    --tour-primary: #2563eb;
    --tour-primary-dark: #1d4ed8;
    --tour-text: #172033;
    --tour-muted: #697386;
    --tour-border: #e7eaf0;
    --tour-bg: #f5f7fb;

    padding: 28px;
    background: var(--tour-bg);
    color: var(--tour-text);
    min-height: 80vh;
    font-family: Inter, "Segoe UI", Arial, sans-serif;
}

.tour-container {
    max-width: 1380px;
    margin: 0 auto;
}

/* Hero section */

.tour-hero {
    position: relative;
    overflow: hidden;
    padding: 45px 42px;
    border-radius: 22px;
    margin-bottom: 30px;
    background:
        radial-gradient(
            circle at 85% 10%,
            rgba(96, 165, 250, .45),
            transparent 30%
        ),
        linear-gradient(125deg, #10264c 0%, #1d4ed8 65%, #3b82f6 100%);
    color: #fff;
}

.tour-hero::after {
    content: "";
    position: absolute;
    width: 270px;
    height: 270px;
    right: -70px;
    bottom: -160px;
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 50%;
    box-shadow:
        0 0 0 30px rgba(255,255,255,.04),
        0 0 0 65px rgba(255,255,255,.035);
    pointer-events: none;
}

.tour-hero-content {
    position: relative;
    z-index: 1;
    max-width: 720px;
}

.tour-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 30px;
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.2);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.3px;
    text-transform: uppercase;
    margin-bottom: 20px;
}

.tour-hero h1 {
    color: #fff;
    font-size: clamp(30px, 4vw, 46px);
    line-height: 1.13;
    letter-spacing: -1.2px;
    font-weight: 800;
    margin: 0 0 15px;
}

.tour-hero p {
    color: rgba(255,255,255,.84);
    max-width: 610px;
    font-size: 15px;
    line-height: 1.8;
    margin: 0;
}

.tour-hero-note {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 25px;
    font-size: 12px;
    font-weight: 600;
    color: rgba(255,255,255,.95);
}

.tour-hero-note span {
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

/* Page heading */

.tour-section-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 18px;
    flex-wrap: wrap;
    margin: 32px 0 20px;
}

.tour-section-heading h2 {
    color: var(--tour-text);
    font-size: 25px;
    font-weight: 800;
    letter-spacing: -.5px;
    margin: 0 0 7px;
}

.tour-section-heading p {
    color: var(--tour-muted);
    font-size: 13px;
    line-height: 1.6;
    margin: 0;
}

.tour-result-count {
    color: var(--tour-primary);
    background: #e8efff;
    padding: 9px 13px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}

/* Search and filters */

.tour-filter-panel {
    display: grid;
    grid-template-columns: minmax(200px, 1fr) minmax(180px, 250px) auto;
    gap: 12px;
    align-items: center;
    background: #fff;
    border: 1px solid var(--tour-border);
    border-radius: 15px;
    padding: 16px;
    margin-bottom: 24px;
    box-shadow: 0 3px 12px rgba(20,35,70,.025);
}

.tour-search-wrap {
    position: relative;
}

.tour-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #8b95a7;
    font-size: 18px;
    pointer-events: none;
}

.tour-search,
.tour-select {
    width: 100%;
    min-height: 45px;
    border: 1px solid #dce2eb;
    border-radius: 9px;
    padding: 11px 13px;
    color: var(--tour-text);
    background: #fff;
    font-family: inherit;
    font-size: 13px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}

.tour-search {
    padding-left: 41px;
}

.tour-search:focus,
.tour-select:focus {
    border-color: var(--tour-primary);
    box-shadow: 0 0 0 3px rgba(37,99,235,.10);
}

.tour-reset {
    border: 1px solid #dce2eb;
    color: #475569;
    background: #fff;
    min-height: 45px;
    padding: 0 16px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 750;
    font-family: inherit;
    cursor: pointer;
    transition: .2s;
}

.tour-reset:hover {
    background: #f8fafc;
    border-color: #b9c4d4;
}

/* Package cards */

.tour-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 23px;
}

.tour-card {
    display: flex;
    flex-direction: column;
    min-width: 0;
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--tour-border);
    border-radius: 17px;
    box-shadow: 0 3px 12px rgba(20,35,70,.035);
    transition: transform .25s, box-shadow .25s, border-color .25s;
}

.tour-card:hover {
    transform: translateY(-5px);
    border-color: #d5e0f5;
    box-shadow: 0 14px 32px rgba(20,35,70,.09);
}

.tour-image {
    position: relative;
    height: 225px;
    overflow: hidden;
    background: linear-gradient(135deg, #dbeafe, #e2e8f0);
}

.tour-image img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .45s;
}

.tour-card:hover .tour-image img {
    transform: scale(1.055);
}

.tour-image-fallback {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 100%;
    gap: 10px;
    color: #64748b;
    font-size: 13px;
}

.tour-image-fallback span {
    font-size: 42px;
}

.tour-image-overlay {
    position: absolute;
    inset: auto 0 0;
    height: 75px;
    background: linear-gradient(transparent, rgba(15,23,42,.38));
    pointer-events: none;
}

.tour-location-chip {
    position: absolute;
    bottom: 15px;
    left: 15px;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    max-width: calc(100% - 30px);
    padding: 8px 11px;
    border-radius: 8px;
    color: #fff;
    background: rgba(15,23,42,.72);
    backdrop-filter: blur(8px);
    font-size: 11px;
    font-weight: 750;
    overflow-wrap: anywhere;
}

.tour-card-content {
    display: flex;
    flex: 1;
    flex-direction: column;
    padding: 22px;
}

.tour-package-title {
    color: var(--tour-text);
    font-size: 20px;
    line-height: 1.4;
    font-weight: 800;
    margin: 0 0 11px;
    overflow-wrap: anywhere;
}

.tour-destination-line {
    display: flex;
    align-items: center;
    gap: 7px;
    color: var(--tour-primary);
    font-size: 12px;
    font-weight: 750;
    margin-bottom: 15px;
    line-height: 1.5;
}

.tour-destination-line span {
    overflow-wrap: anywhere;
}

.tour-description {
    color: #707b8d;
    font-size: 13px;
    line-height: 1.75;
    margin: 0 0 21px;
    overflow-wrap: anywhere;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 68px;
}

.tour-card-bottom {
    margin-top: auto;
}

.tour-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    border-top: 1px solid #edf0f5;
    padding-top: 18px;
    margin-bottom: 18px;
}

.tour-duration-label {
    color: #8a94a6;
    font-size: 11px;
    margin-bottom: 6px;
}

.tour-duration-value {
    color: #344054;
    font-size: 13px;
    font-weight: 750;
}

.tour-price {
    color: #172033;
    font-size: 24px;
    font-weight: 850;
    letter-spacing: -.5px;
    text-align: right;
    overflow-wrap: anywhere;
}

.tour-price-label {
    color: #8a94a6;
    font-size: 10px;
    font-weight: 500;
    margin-top: 4px;
    text-align: right;
}

.tour-book-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    width: 100%;
    min-height: 47px;
    padding: 12px 15px;
    background: var(--tour-primary);
    border: 1px solid var(--tour-primary);
    border-radius: 9px;
    color: #fff !important;
    text-decoration: none !important;
    text-align: center;
    font-family: inherit;
    font-size: 13px;
    font-weight: 800;
    transition: background .2s, transform .2s, box-shadow .2s;
}

.tour-book-button:hover {
    background: var(--tour-primary-dark);
    border-color: var(--tour-primary-dark);
    box-shadow: 0 5px 13px rgba(37,99,235,.22);
}

.tour-book-button:focus-visible,
.tour-reset:focus-visible {
    outline: 3px solid rgba(37,99,235,.35);
    outline-offset: 3px;
}

/* Empty states */

.tour-empty {
    grid-column: 1 / -1;
    background: #fff;
    border: 1px solid var(--tour-border);
    border-radius: 16px;
    padding: 55px 20px;
    text-align: center;
}

.tour-empty-icon {
    font-size: 43px;
    margin-bottom: 14px;
}

.tour-empty h3 {
    color: var(--tour-text);
    font-size: 20px;
    font-weight: 800;
    margin: 0 0 10px;
}

.tour-empty p {
    color: var(--tour-muted);
    font-size: 13px;
    line-height: 1.7;
    margin: 0;
}

.tour-hidden {
    display: none !important;
}

/* Trust strip */

.tour-trust-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
    margin-top: 28px;
}

.tour-trust-item {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fff;
    border: 1px solid var(--tour-border);
    border-radius: 12px;
    padding: 17px;
}

.tour-trust-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 39px;
    height: 39px;
    border-radius: 10px;
    background: #eff6ff;
    color: var(--tour-primary);
    font-size: 18px;
}

.tour-trust-item strong {
    display: block;
    color: #263248;
    font-size: 12px;
    margin-bottom: 5px;
}

.tour-trust-item small {
    display: block;
    color: #8a94a6;
    font-size: 11px;
    line-height: 1.5;
}

/* Responsive layouts */

@media (max-width: 1100px) {
    .tour-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .tour-image {
        height: 215px;
    }
}

@media (max-width: 750px) {
    .tour-page {
        padding: 18px 13px;
    }

    .tour-hero {
        padding: 32px 24px;
        border-radius: 16px;
    }

    .tour-filter-panel {
        grid-template-columns: 1fr 1fr;
    }

    .tour-search-wrap {
        grid-column: 1 / -1;
    }

    .tour-reset {
        width: 100%;
    }

    .tour-trust-strip {
        grid-template-columns: 1fr;
    }

    .tour-section-heading h2 {
        font-size: 22px;
    }
}

@media (max-width: 520px) {
    .tour-grid {
        grid-template-columns: 1fr;
        gap: 17px;
    }

    .tour-hero {
        padding: 27px 20px;
    }

    .tour-hero h1 {
        font-size: 31px;
    }

    .tour-hero p {
        font-size: 13px;
    }

    .tour-hero-note {
        gap: 12px;
        flex-direction: column;
        align-items: flex-start;
    }

    .tour-filter-panel {
        grid-template-columns: 1fr;
        padding: 12px;
    }

    .tour-search-wrap {
        grid-column: auto;
    }

    .tour-select,
    .tour-reset {
        width: 100%;
    }

    .tour-image {
        height: 220px;
    }

    .tour-card-content {
        padding: 19px;
    }

    .tour-package-title {
        font-size: 19px;
    }

    .tour-section-heading {
        align-items: flex-start;
    }
}
</style>

<main class="tour-page">
    <div class="tour-container">

        <!-- Hero -->
        <section class="tour-hero">
            <div class="tour-hero-content">
                <div class="tour-eyebrow">
                    <span>✦</span>
                    Your next adventure starts here
                </div>

                <h1>Discover Places.<br>Collect Memories.</h1>

                <p>
                    Explore handpicked tour packages, discover amazing
                    destinations, and find a trip that fits your travel plans
                    and budget.
                </p>

                <div class="tour-hero-note">
                    <span>✓ Explore destinations</span>
                    <span>✓ Transparent per-person pricing</span>
                    <span>✓ Easy online booking</span>
                </div>
            </div>
        </section>

        <!-- Heading -->
        <section class="tour-section-heading">
            <div>
                <h2>Explore Tour Packages</h2>
                <p>Find your next getaway from our available travel packages.</p>
            </div>

            <div class="tour-result-count">
                <span id="tourResultCount"><?php echo count($packageList); ?></span>
                Packages Available
            </div>
        </section>

        <!-- Search and filters -->
        <?php if (count($packageList) > 0): ?>
            <section class="tour-filter-panel" aria-label="Search tour packages">

                <div class="tour-search-wrap">
                    <span class="tour-search-icon" aria-hidden="true">⌕</span>
                    <input
                        type="search"
                        id="tourSearch"
                        class="tour-search"
                        placeholder="Search packages or destinations..."
                        aria-label="Search packages or destinations"
                    >
                </div>

                <select
                    id="tourDestination"
                    class="tour-select"
                    aria-label="Filter by destination"
                >
                    <option value="">All destinations</option>
                    <?php foreach ($destinationsList as $destination): ?>
                        <option value="<?php echo htmlspecialchars($destination, ENT_QUOTES, "UTF-8"); ?>">
                            <?php echo htmlspecialchars($destination, ENT_QUOTES, "UTF-8"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="button" id="tourReset" class="tour-reset">
                    Reset filters
                </button>

            </section>
        <?php endif; ?>

        <!-- Package grid -->
        <section
            class="tour-grid"
            id="tourPackageGrid"
            aria-label="Available tour packages"
        >
            <?php if (count($packageList) > 0): ?>

                <?php foreach ($packageList as $package): ?>
                    <?php
                    $packageName = $package["package_name"] ?? "Tour Package";
                    $destinationName = $package["destination_name"] ?? "";
                    $destinationLocation = $package["destination_location"] ?? "";
                    $description = trim($package["description"] ?? "");
                    $imagePath = trim($package["destination_image"] ?? "");
                    $duration = $package["duration"] ?? "";
                    $price = (float) ($package["price"] ?? 0);

                    $searchText = strtolower(
                        $packageName . " " .
                        $destinationName . " " .
                        $destinationLocation . " " .
                        $description
                    );
                    ?>

                    <article
                        class="tour-card"
                        data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, "UTF-8"); ?>"
                        data-destination="<?php echo htmlspecialchars($destinationName, ENT_QUOTES, "UTF-8"); ?>"
                    >

                        <!-- Image -->
                        <div class="tour-image">

                            <?php if ($imagePath !== ""): ?>
                                <img
                                    src="../<?php echo htmlspecialchars($imagePath, ENT_QUOTES, "UTF-8"); ?>"
                                    alt="<?php echo htmlspecialchars($destinationName . " - " . $packageName, ENT_QUOTES, "UTF-8"); ?>"
                                    loading="lazy"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >

                                <div class="tour-image-fallback" style="display:none;">
                                    <span aria-hidden="true">🏞</span>
                                    Image unavailable
                                </div>
                            <?php else: ?>
                                <div class="tour-image-fallback">
                                    <span aria-hidden="true">🏞</span>
                                    Destination image unavailable
                                </div>
                            <?php endif; ?>

                            <div class="tour-image-overlay"></div>

                            <div class="tour-location-chip">
                                <span aria-hidden="true">📍</span>
                                <?php
                                echo htmlspecialchars(
                                    $destinationLocation !== ""
                                        ? $destinationLocation
                                        : $destinationName,
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>
                            </div>
                        </div>

                        <!-- Package content -->
                        <div class="tour-card-content">

                            <h3 class="tour-package-title">
                                <?php
                                echo htmlspecialchars(
                                    $packageName,
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>
                            </h3>

                            <div class="tour-destination-line">
                                <span aria-hidden="true">✦</span>
                                <span>
                                    <?php
                                    echo htmlspecialchars(
                                        $destinationName,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </span>
                            </div>

                            <p class="tour-description">
                                <?php
                                echo htmlspecialchars(
                                    $description !== ""
                                        ? $description
                                        : "Discover this destination with a thoughtfully planned tour package.",
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>
                            </p>

                            <div class="tour-card-bottom">

                                <div class="tour-details">
                                    <div>
                                        <div class="tour-duration-label">
                                            TRIP DURATION
                                        </div>

                                        <div class="tour-duration-value">
                                            ◷
                                            <?php
                                            echo htmlspecialchars(
                                                (string) $duration,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );
                                            ?>
                                            <?php echo ((string) $duration === "1") ? "day" : "days"; ?>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="tour-price">
                                            ₹<?php echo number_format($price, 2); ?>
                                        </div>

                                        <div class="tour-price-label">
                                            Price per person
                                        </div>
                                    </div>
                                </div>

                                <a
                                    class="tour-book-button"
                                    href="book.php?id=<?php echo (int) $package["id"]; ?>"
                                >
                                    Book This Package
                                    <span aria-hidden="true">→</span>
                                </a>

                            </div>
                        </div>
                    </article>

                <?php endforeach; ?>

                <!-- Search no-results state -->
                <div
                    id="tourNoMatches"
                    class="tour-empty tour-hidden"
                    role="status"
                    aria-live="polite"
                >
                    <div class="tour-empty-icon" aria-hidden="true">⌕</div>
                    <h3>No matching packages found</h3>
                    <p>Try another search term or choose a different destination.</p>
                </div>

            <?php else: ?>

                <div class="tour-empty">
                    <div class="tour-empty-icon" aria-hidden="true">✈</div>
                    <h3>No Tour Packages Available</h3>
                    <p>
                        There are currently no active tour packages.
                        Please check again later for new destinations and trips.
                    </p>
                </div>

            <?php endif; ?>
        </section>

        <?php if (count($packageList) > 0): ?>
            <!-- Customer confidence section -->
            <section class="tour-trust-strip" aria-label="Travel highlights">

                <div class="tour-trust-item">
                    <div class="tour-trust-icon" aria-hidden="true">◎</div>
                    <div>
                        <strong>Discover New Places</strong>
                        <small>Explore the destinations available in our collection.</small>
                    </div>
                </div>

                <div class="tour-trust-item">
                    <div class="tour-trust-icon" aria-hidden="true">₹</div>
                    <div>
                        <strong>Clear Package Pricing</strong>
                        <small>View the listed price per person before booking.</small>
                    </div>
                </div>

                <div class="tour-trust-item">
                    <div class="tour-trust-icon" aria-hidden="true">↗</div>
                    <div>
                        <strong>Easy Booking Process</strong>
                        <small>Choose a package to continue to its booking page.</small>
                    </div>
                </div>

            </section>
        <?php endif; ?>

    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("tourSearch");
    const destinationSelect = document.getElementById("tourDestination");
    const resetButton = document.getElementById("tourReset");
    const cards = Array.from(document.querySelectorAll(".tour-card"));
    const resultCount = document.getElementById("tourResultCount");
    const noMatches = document.getElementById("tourNoMatches");

    if (!searchInput || !destinationSelect || !resetButton) {
        return;
    }

    function filterPackages() {
        const search = searchInput.value.trim().toLowerCase();
        const selectedDestination =
            destinationSelect.value.trim().toLowerCase();

        let visibleCount = 0;

        cards.forEach(function (card) {
            const cardText = card.dataset.search || "";
            const cardDestination =
                (card.dataset.destination || "").toLowerCase();

            const matchesSearch = cardText.includes(search);
            const matchesDestination =
                selectedDestination === "" ||
                cardDestination === selectedDestination;

            const visible = matchesSearch && matchesDestination;

            card.classList.toggle("tour-hidden", !visible);

            if (visible) {
                visibleCount++;
            }
        });

        if (resultCount) {
            resultCount.textContent = visibleCount;
        }

        if (noMatches) {
            noMatches.classList.toggle(
                "tour-hidden",
                visibleCount !== 0
            );
        }
    }

    searchInput.addEventListener("input", filterPackages);
    destinationSelect.addEventListener("change", filterPackages);

    resetButton.addEventListener("click", function () {
        searchInput.value = "";
        destinationSelect.value = "";
        filterPackages();
        searchInput.focus();
    });
});
</script>

<?php require_once "../includes/customer_footer.php"; ?>
