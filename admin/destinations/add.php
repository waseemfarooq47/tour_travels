
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: /tour-travel/auth/login.php");
    exit();
}

require_once "../../config/database.php";

$message = "";
$name = "";
$location = "";
$description = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $description = trim($_POST["description"] ?? "");

    $imagePath = null;
    $uploadedFile = null;

    if ($name === "" || $location === "") {
        $message = "Please fill in all required fields.";
    } else {

        // Process optional image upload
        if (
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {
            $file = $_FILES["image"];

            if ($file["error"] !== UPLOAD_ERR_OK) {
                $message = "Image upload failed. Please try again.";
            } elseif ($file["size"] > 5 * 1024 * 1024) {
                $message = "Image must be 5 MB or smaller.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file["tmp_name"]);

                $allowedTypes = [
                    "image/jpeg" => "jpg",
                    "image/png"  => "png",
                    "image/webp" => "webp"
                ];

                if (!isset($allowedTypes[$mimeType])) {
                    $message = "Please upload a JPG, PNG, or WebP image.";
                } else {
                    $uploadDir = dirname(__DIR__, 2)
                               . "/uploads/destinations/";

                    if (
                        !is_dir($uploadDir) &&
                        !mkdir($uploadDir, 0755, true) &&
                        !is_dir($uploadDir)
                    ) {
                        $message = "Unable to create the image folder.";
                    } else {
                        $fileName = bin2hex(random_bytes(16))
                                  . "." . $allowedTypes[$mimeType];

                        $destination = $uploadDir . $fileName;

                        if (move_uploaded_file(
                            $file["tmp_name"],
                            $destination
                        )) {
                            $imagePath = "uploads/destinations/" . $fileName;
                            $uploadedFile = $destination;
                        } else {
                            $message = "Unable to save the uploaded image.";
                        }
                    }
                }
            }
        }

        // Save destination details
        if ($message === "") {

            $stmt = $conn->prepare(
                "INSERT INTO destinations
                (name, location, description, image)
                VALUES (?, ?, ?, ?)"
            );

            if ($stmt) {
                $stmt->bind_param(
                    "ssss",
                    $name,
                    $location,
                    $description,
                    $imagePath
                );

                if ($stmt->execute()) {
                    $stmt->close();

                    header(
                        "Location: /tour-travel/admin/destinations/index.php?added=1"
                    );
                    exit();
                } else {
                    $message = "Unable to add destination. Please try again.";
                    $stmt->close();
                }
            } else {
                $message = "Unable to prepare the database query.";
            }

            // Remove uploaded file if saving the database record failed
            if (
                $message !== "" &&
                $uploadedFile !== null &&
                is_file($uploadedFile)
            ) {
                unlink($uploadedFile);
            }
        }
    }
}

require_once "../../includes/admin_header.php";
?>

<style>
.destination-form-page {
    max-width: 1100px;
    margin: 0 auto;
    padding-bottom: 30px;
}

.destination-page-heading {
    margin-bottom: 24px;
}

.destination-page-heading h1 {
    margin: 0 0 8px;
    color: #172033;
    font-size: 28px;
}

.destination-page-heading p {
    margin: 0;
    color: #7a8496;
    font-size: 13px;
    line-height: 1.6;
}

.destination-form-card {
    background: #fff;
    border: 1px solid #e7eaf0;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

.destination-form-card-header {
    padding: 22px 26px;
    border-bottom: 1px solid #edf0f4;
}

.destination-form-card-header h2 {
    margin: 0 0 6px;
    color: #172033;
    font-size: 17px;
}

.destination-form-card-header p {
    margin: 0;
    color: #8490a3;
    font-size: 12px;
}

.destination-form {
    padding: 26px;
}

.form-group {
    margin-bottom: 22px;
}

.form-group label {
    display: block;
    margin-bottom: 9px;
    color: #344054;
    font-size: 13px;
    font-weight: 600;
}

.required {
    color: #dc2626;
}

.form-control {
    display: block;
    width: 100%;
    box-sizing: border-box;
    padding: 12px 14px;
    border: 1px solid #d9dee7;
    border-radius: 7px;
    color: #172033;
    background: #fff;
    font-family: inherit;
    font-size: 13px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}

.form-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .09);
}

input[type="file"].form-control {
    padding: 10px;
}

textarea.form-control {
    min-height: 140px;
    resize: vertical;
    line-height: 1.6;
}

.field-help {
    display: block;
    margin-top: 7px;
    color: #98a2b3;
    font-size: 11px;
    line-height: 1.5;
}

.form-error {
    margin-bottom: 22px;
    padding: 13px 15px;
    border: 1px solid #fecaca;
    border-radius: 7px;
    background: #fef2f2;
    color: #b91c1c;
    font-size: 13px;
}

.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid #edf0f4;
}

.form-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 19px;
    border: none;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .2s;
}

.save-button {
    background: #2563eb;
    color: #fff;
}

.save-button:hover {
    background: #1d4ed8;
    color: #fff;
}

.cancel-button {
    background: #f3f4f6;
    color: #475467;
}

.cancel-button:hover {
    background: #e5e7eb;
    color: #172033;
}

@media (max-width: 600px) {
    .destination-page-heading h1 {
        font-size: 23px;
    }

    .destination-form,
    .destination-form-card-header {
        padding: 20px;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-button {
        width: 100%;
        box-sizing: border-box;
    }
}
</style>

<div class="destination-form-page">

    <div class="destination-page-heading">
        <h1>Add Destination</h1>
        <p>
            Add a new travel destination to your system.
            Enter the destination details and upload an optional image.
        </p>
    </div>

    <div class="destination-form-card">

        <div class="destination-form-card-header">
            <h2>Destination Information</h2>
            <p>Fields marked with * are required.</p>
        </div>

        <form
            method="POST"
            class="destination-form"
            enctype="multipart/form-data"
        >

            <?php if ($message !== ""): ?>
                <div class="form-error">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="name">
                    Destination Name <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control"
                    placeholder="e.g. Pahalgam"
                    value="<?php echo htmlspecialchars($name, ENT_QUOTES, "UTF-8"); ?>"
                    maxlength="150"
                    required
                >
            </div>

            <div class="form-group">
                <label for="location">
                    Location <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="location"
                    name="location"
                    class="form-control"
                    placeholder="e.g. Anantnag, Kashmir"
                    value="<?php echo htmlspecialchars($location, ENT_QUOTES, "UTF-8"); ?>"
                    maxlength="150"
                    required
                >
            </div>

            <div class="form-group">
                <label for="image">Destination Image (Optional)</label>

                <input
                    type="file"
                    id="image"
                    name="image"
                    class="form-control"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >

                <span class="field-help">
                    Upload a JPG, PNG, or WebP image.
                    Maximum file size: 5 MB.
                    Leave empty if no image is available.
                </span>
            </div>

            <div class="form-group">
                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    class="form-control"
                    placeholder="Describe the destination and its main attractions..."
                ><?php echo htmlspecialchars($description, ENT_QUOTES, "UTF-8"); ?></textarea>

                <span class="field-help">
                    Add a short description to help customers understand
                    what makes this destination special.
                </span>
            </div>

            <div class="form-actions">

                <button
                    type="submit"
                    class="form-button save-button"
                >
                    Save Destination
                </button>

                <a
                    href="/tour-travel/admin/destinations/index.php"
                    class="form-button cancel-button"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

<?php require_once "../../includes/admin_footer.php"; ?>
