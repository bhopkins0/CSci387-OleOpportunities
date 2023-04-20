<?php
include 'resources/functions.php';
if (!empty($_GET["listing_ID"]) && !empty($_POST["comment"]) && isLoggedIn())
    addComment($_GET["listing_ID"], $_POST["comment"]);
if (!empty($_GET["listing_ID"]) && !empty($_POST["rating"]) && isLoggedIn())
    addIndRating($_GET["listing_ID"], $_POST["rating"]);
if (!empty($_GET["listing_ID"]) && !empty($_POST["savelisting"]) && isLoggedIn())
    toggleListingSave($_GET["listing_ID"]);
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OleOpportunities - Listings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="/resources/bootstrap.min.css" rel="stylesheet">
    <style>

        /* Show it is fixed to the top */
        body {
            padding-top: 4.5rem;
        }
        .search{
            position: relative;
            box-shadow: 0 0 40px rgba(51, 51, 51, .1);

        }

        .search input{

            height: 60px;
            text-indent: 25px;
        }


        .search input:focus{
            box-shadow: none;
        }

        .search .fa-search{
            position: absolute;
            top: 20px;
            left: 16px;

        }

        .search button{

            position: absolute;
            top: 5px;
            right: 5px;
            height: 50px;
            width: 100px;
            background: blue;

        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            padding: 10px 10px 10px 0;
        }

        .star-rating input[type=radio] {
            display: none;
        }

        .star-rating label {
            color: #bbb;
            font-size: 18px;
            padding: 0;
            cursor: pointer;
            transition: all .3s ease-in-out
        }

        .star-rating label:hover,
        .star-rating label:hover~label,
        .star-rating input[type=radio]:checked~label {
            color: orange;
        }
        .checked-star {
            color: orange;
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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
                aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="listings.php">View Listings</a>
                </li>
                <?php
                loadAddListingsPage();
                loadAdminInNavbar();
                ?>
            </ul>
            <?php
            loadNavbarDropdown();
            ?>
        </div>
    </div>
</nav>


<?php

if (isset($_GET["listing_ID"]))
    displayListing($_GET["listing_ID"]);
else
    displayAllListings();
?>

<script src="resources/bootstrap.bundle.min.js"></script>


</body>
</html>
