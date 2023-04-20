<?php
include 'resources/functions.php';
if (!isLoggedIn()) {
    header('Location: index.php');
    die();
}
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OleOpportunities - Home</title>
    <link href="/resources/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Show it is fixed to the top */
        body {
            padding-top: 4.5rem;
        }

        .bd-placeholder-img {
            font-size: 1.125rem;
            text-anchor: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
        }

        @media (min-width: 768px) {
            .bd-placeholder-img-lg {
                font-size: 3.5rem;
            }
        }

        .b-example-divider {
            width: 100%;
            height: 3rem;
            background-color: rgba(0, 0, 0, .1);
            border: solid rgba(0, 0, 0, .15);
            border-width: 1px 0;
            box-shadow: inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15);
        }

        .b-example-vr {
            flex-shrink: 0;
            width: 1.5rem;
            height: 100vh;
        }

        .bi {
            vertical-align: -.125em;
            fill: currentColor;
        }

        .nav-scroller {
            position: relative;
            z-index: 2;
            height: 2.75rem;
            overflow-y: hidden;
        }

        .nav-scroller .nav {
            display: flex;
            flex-wrap: nowrap;
            padding-bottom: 1rem;
            margin-top: -1px;
            overflow-x: auto;
            text-align: center;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .btn-bd-primary {
            --bd-violet-bg: #712cf9;
            --bd-violet-rgb: 112.520718, 44.062154, 249.437846;

            --bs-btn-font-weight: 600;
            --bs-btn-color: var(--bs-white);
            --bs-btn-bg: var(--bd-violet-bg);
            --bs-btn-border-color: var(--bd-violet-bg);
            --bs-btn-hover-color: var(--bs-white);
            --bs-btn-hover-bg: #6528e0;
            --bs-btn-hover-border-color: #6528e0;
            --bs-btn-focus-shadow-rgb: var(--bd-violet-rgb);
            --bs-btn-active-color: var(--bs-btn-hover-color);
            --bs-btn-active-bg: #5a23c8;
            --bs-btn-active-border-color: #5a23c8;
        }
        .bd-mode-toggle {
            z-index: 1500;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">OleOpportunities</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="listings.php">View Listings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="listings.php">Add Listing</a>
                </li>
                <?php
                loadAdminInNavbar();
                ?>
            </ul>
            <?php
            loadNavbarDropdown();
            ?>
        </div>
    </div>
</nav>

<main class="container mb-2">
    <div class="bg-body-tertiary p-5 rounded">
        <h1>Add Listing</h1>
        <p class="lead text-muted">All listings must be approved by an admin before being publicly listed</p>
        <hr>
        <form method="POST">
            <?php
            if (!empty($_POST["title"]) && !empty($_POST["description"]) && !empty($_POST["url"]) && !empty($_POST["startdate"]) && !empty($_POST["type"]))
                addListing($_POST["title"], $_POST["description"], $_POST["url"], $_POST["startdate"], $_POST["type"]);
            ?>
            <div class="mb-3">
                <label for="title" class="form-label">Listing Title</label>
                <input type="text" class="form-control" id="title" name="title" placeholder="Software Engineer Internship | Example Inc">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Listing Description</label>
                <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                <small>Maximum 1000 letters</small>
            </div>
            <div class="mb-3">
                <label for="url" class="form-label">URL - Do not include http://</label>
                <input type="text" class="form-control" id="url" name="url" placeholder="www.example.com">
            </div>
            <div class="mb-3">
                <label for="startdate" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="startdate" name="startdate">
            </div>
            <div class="mb-3">
                <label for="listing" class="form-label">Listing Type</label>
                <select class="form-select" name="type" aria-label="Listing Type">
                    <option selected>Listing Type</option>
                    <option value="1">Job</option>
                    <option value="2">Internship</option>
                    <option value="3">Research</option>
                </select>
            </div>
            <button class="w-100 btn btn-lg btn-outline-primary" type="submit">Submit Listing</button>
        </form>
    </div>
</main>


<script src="resources/bootstrap.bundle.min.js"></script>


</body>
</html>
