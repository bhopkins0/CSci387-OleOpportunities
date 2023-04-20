<?php
session_start();
function startMySQL(): bool|mysqli
{
    $mysqlHostname = "";
    $mysqlUser = "";
    $mysqlPass = '';
    $mysqlDB = "";

    return mysqli_connect($mysqlHostname, $mysqlUser, $mysqlPass, $mysqlDB);
}

/*
 *
 * Admin Page
 *
 */

function adminAction($action)
{
    switch ($action) {
        case 1:
            return displayUnapprovedListings();
        case 2:
            return displayAllComments();
        case 3:
            return displayAdminGrant();
    }
}


/*
 *
 * Grant Admin Privileges (Admin Page)
 *
 */

function displayAdminGrant()
{
    $displayAdminForm = "<hr>";
    if (!empty($_POST["email"]))
        $displayAdminForm .= grantAdmin($_POST["email"]);
        $displayAdminForm .= <<<EOL
<h3>Grant Admin Privileges</h3>
        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter user's email">
            </div>
        <input type="hidden" name="action" value="3">
        <button class="btn btn-lg btn-primary w-100 mt-1" type="submit" role="button">Grant Admin Privileges</button>
        </form>
EOL;
    return $displayAdminForm;

}

function grantAdmin($email) {
    if (!isEmailUsed($email))
        return "<div class='alert alert-danger' role='alert'>Error: Invalid Email</div>";
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "UPDATE User SET usertype_ID = 2 WHERE email = ?";
    $stmt = $mysqlConn->prepare($sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return "<div class='alert alert-success' role='alert'>Successfully granted admin privileges to ".htmlspecialchars($email).".</div>";
}

/*
 *
 * Comment Management (Admin Page)
 *
 */

function displayAllComments()
{
    $interact_ID = getUserInteractTypeID("comment");
    $displayedComments = "<hr>";
    if (!empty($_POST["deleteComment"]))
        $displayedComments .= deleteComment($_POST["deleteComment"]);
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT * FROM User_Interaction WHERE user_interact_type_ID = ?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $interact_ID);
    $stmt->execute();
    $result = $stmt->get_result();
    for ($i = 0; $i < $result->num_rows; $i++) {
        $commentInfo = $result->fetch_assoc();
        $commentOwner = getNameFromUserID($commentInfo["user_ID"]);
        htmlspecialcharsArray($commentInfo);
        htmlspecialcharsArray($commentOwner);
        $displayedComments .= <<<EOL
<h1>{$commentOwner["first_name"]} {$commentOwner["last_name"]} <small>(User ID: {$commentInfo["user_ID"]})</small></h1>
        <p class="lead">{$commentInfo["comment"]}</p>
        <form method="POST">
        <input type="hidden" name="deleteComment" value="{$commentInfo["user_interact_ID"]}">
        <input type="hidden" name="action" value="2">
        <button class="btn btn-lg btn-danger w-100 mt-2" type="submit" role="button">Delete listing</button>
        </form>
EOL;
        if ($i != $result->num_rows - 1)
            $displayedComments .= '<hr>';
    }
    return $displayedComments;

    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);

}


function deleteComment($user_interact_ID)
{
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "DELETE FROM User_Interaction WHERE user_interact_ID=?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $user_interact_ID);
    $stmt->execute();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return "<div class='alert alert-success' role='alert'>Comment Deleted</div>";

}

/*
 *
 * Listing Manager (Admin Page)
 *
 */

function approveListing($opportunity_ID)
{
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "UPDATE Opportunity SET approved=1 WHERE opportunity_ID=?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $opportunity_ID);
    $stmt->execute();

    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return "<div class='alert alert-success' role='alert'>Opportunity Approved</div>";

}

function deleteListing($opportunity_ID)
{
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "DELETE FROM User_Interaction WHERE opportunity_ID=?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $opportunity_ID);
    $stmt->execute();
    $sql = "DELETE FROM Opportunity_Tag_Join WHERE opportunity_ID=?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $opportunity_ID);
    $stmt->execute();
    $sql = "DELETE FROM Opportunity WHERE opportunity_ID=?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $opportunity_ID);
    $stmt->execute();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return "<div class='alert alert-success' role='alert'>Opportunity Deleted</div>";

}


function displayUnapprovedListings()
{
    $unapprovedListings = "<hr>";
    if (!empty($_POST["approveOpportunity"]))
        $unapprovedListings .= approveListing($_POST["approveOpportunity"]);
    if (!empty($_POST["deleteOpportunity"]))
        $unapprovedListings .= deleteListing($_POST["deleteOpportunity"]);
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT * FROM Opportunity WHERE approved = 0";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    for ($i = 0; $i < $result->num_rows; $i++) {
        $opportunityInfo = $result->fetch_assoc();
        htmlspecialcharsArray($opportunityInfo);
        $opportunityType = strtoupper(getOpportunityTypeFromID($opportunityInfo["opp_type_ID"]));
        $opportunityTags = "";
        $ratingStars = getRatingStars($opportunityInfo["opp_rating"]);
        if (getTagsOnOpportunity($opportunityInfo["opportunity_ID"])) {
            foreach ((array)getTagsOnOpportunity($opportunityInfo["opportunity_ID"]) as $tag) {
                if (!getTagDescription($tag["opp_tag_join_ID"]))
                    continue;
                $opportunityTags .= '<span class="badge bg-secondary me-1">' . getTagDescription($tag["opp_tag_join_ID"]) . '</span>';
            }
        }
        $unapprovedListings .= <<<EOL
<h1>{$opportunityInfo["title"]} <small>$ratingStars</small></h1>
        <h5><span class="badge bg-secondary me-1">{$opportunityType}</span> {$opportunityTags}</h5>
        <p class="lead">{$opportunityInfo["description"]}</p>
        <p class="text-muted">Start date: {$opportunityInfo["start_date"]}</p>
        <p>URL: {$opportunityInfo["URL"]}</p>
        <form method="POST">
        <input type="hidden" name="approveOpportunity" value="{$opportunityInfo["opportunity_ID"]}">
        <input type="hidden" name="action" value="1">
        <button class="btn btn-lg btn-success w-100" type="submit" role="button">Approve listing</button>
        </form>
        <form method="POST">
        <input type="hidden" name="deleteOpportunity" value="{$opportunityInfo["opportunity_ID"]}">
        <input type="hidden" name="action" value="1">
        <button class="btn btn-lg btn-danger w-100 mt-2" type="submit" role="button">Delete listing</button>
        </form>
EOL;
        if ($i != $result->num_rows - 1)
            $unapprovedListings .= '<hr>';
    }
    return $unapprovedListings;

    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);

}


/*
 *
 * My Account
 *
 */

function accountAction($action)
{
    switch ($action) {
        case 1:
            return displaySavedListings();
        case 2:
            return displaySubmittedListings();
        case 3:
            return displayPasswordReset();
    }
}

/*
 *
 * Password Reset (My Account)
 *
 */


function displayPasswordReset()
{
    $resultMsg = "";
    if (!empty($_POST["oldpassword"]) && !empty($_POST["newpassword"]) && !empty($_POST["repeat"]))
        $resultMsg = resetPassword($_POST["oldpassword"], $_POST["newpassword"], $_POST["repeat"]);
    echo <<<EOL
        <hr>
        <form method="POST">
        <input type="hidden" name="action" value="3">
        {$resultMsg}
            <div class="mb-3">
                <label for="oldpassword" class="form-label">Current password</label>
                <input type="password" class="form-control" id="oldpassword" name="oldpassword" placeholder="Enter current password">
            </div>
            <div class="mb-3">
                <label for="newpassword" class="form-label">New password</label>
                <input type="password" class="form-control" id="newpassword" name="newpassword" placeholder="Enter new password">
            </div>
            <div class="mb-3">
                <label for="repeat" class="form-label">Repeat new password</label>
                <input type="password" class="form-control" id="repeat" name="repeat" placeholder="Repeat new password">
            </div>
            <button class="w-100 btn btn-lg btn-outline-primary" type="submit">Change password</button>
        </form>
EOL;
}

function resetPassword($current, $new, $repeatNew)
{
    if (!accountLogin($_SESSION["email"], $current)) {
        return "<div class='alert alert-danger' role='alert'>The current password you entered was incorrect.</div>";
    }
    if (!($new == $repeatNew))
        return "<div class='alert alert-danger' role='alert'>The passwords do not match</div>";

    if (strlen($new) < 8 || strlen($new) > 64)
        return "<div class='alert alert-danger' role='alert'>Passwords must be in between 8 and 64 characters</div>";

    $newPassword = password_hash($new, PASSWORD_BCRYPT);
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "UPDATE User SET password=? WHERE email=?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("ss", $newPassword, $_SESSION["email"]);
    $stmt->execute();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return "<div class='alert alert-success' role='alert'>Password successfully reset</div>";

}

/*
 *
 * Show Submitted Listings (My Account)
 *
 */

function displaySubmittedListings()
{
    $submittedListings = getSubmittedListings();
    $displayedListings = "<hr>";
    $opportunityTags = "";
    if (!$submittedListings) {
        return '<hr><p class="lead">You have not submitted any listings.</p>';
    }
    for ($i = 0; sizeof($submittedListings) > $i; $i++) {
        $opportunityInfo = getOpportunityInfo($submittedListings[$i]["opportunity_ID"]);
        $opportunityType = strtoupper(getOpportunityTypeFromID($opportunityInfo["opp_type_ID"]));
        htmlspecialcharsArray($opportunityInfo);
        if (getTagsOnOpportunity($opportunityInfo["opportunity_ID"])) {
            foreach ((array)getTagsOnOpportunity($opportunityInfo["opportunity_ID"]) as $tag) {
                if (!getTagDescription($tag["opp_tag_join_ID"]))
                    continue;
                $opportunityTags .= '<span class="badge bg-secondary me-1">' . getTagDescription($tag["opp_tag_join_ID"]) . '</span>';
            }
        }
        $displayedListings .= <<<EOL
<h1>{$opportunityInfo["title"]}</h1>
        <h5><span class="badge bg-secondary me-1">{$opportunityType}</span> {$opportunityTags}</h5>
        <p class="lead">{$opportunityInfo["description"]}</p>
        <p class="text-muted">Start date: {$opportunityInfo["start_date"]}</p>
EOL;
        if ($opportunityInfo["approved"])
            $displayedListings .= '<a class="btn btn-lg btn-primary" href="listings.php?listing_ID=' . $opportunityInfo["opportunity_ID"] . '" role="button">View listing &raquo;</a>';
        else
            $displayedListings .= '<a class="btn btn-lg btn-danger disabled" role="button">Listing has not been approved</a>';
        if ($i != sizeof($submittedListings) - 1)
            $displayedListings .= '<hr>';
    }
    return $displayedListings;
}

function getSubmittedListings()
{
    $interact_ID = getUserInteractTypeID("create");
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT opportunity_ID FROM User_Interaction WHERE user_ID = ? AND user_interact_type_ID = ?";
    $stmt = $mysqlConn->prepare($sql);
    mysqli_stmt_bind_param($stmt, "ii", $_SESSION["user_ID"], $interact_ID);
    mysqli_stmt_execute($stmt);
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $savedListings = array();
        while ($row = $result->fetch_assoc()) {
            $savedListings[] = $row;
        }
        return $savedListings;
    } else {
        return false;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);

}

/*
 *
 * Show Saved Listings (My Account)
 *
 */
function displaySavedListings()
{
    $savedListings = getSavedListings();
    $displayedListings = "<hr>";
    $opportunityTags = "";
    if (!$savedListings) {
        return '<hr><p class="lead">You do not have any saved listings.</p>';
    }
    for ($i = 0; sizeof($savedListings) > $i; $i++) {
        $opportunityInfo = getOpportunityInfo($savedListings[$i]["opportunity_ID"]);
        $opportunityType = strtoupper(getOpportunityTypeFromID($opportunityInfo["opp_type_ID"]));
        htmlspecialcharsArray($opportunityInfo);
        if (getTagsOnOpportunity($opportunityInfo["opportunity_ID"])) {
            foreach ((array)getTagsOnOpportunity($opportunityInfo["opportunity_ID"]) as $tag) {
                if (!getTagDescription($tag["opp_tag_join_ID"]))
                    continue;
                $opportunityTags .= '<span class="badge bg-secondary me-1">' . getTagDescription($tag["opp_tag_join_ID"]) . '</span>';
            }
        }
        $displayedListings .= <<<EOL
<h1>{$opportunityInfo["title"]}</h1>
        <h5><span class="badge bg-secondary me-1">{$opportunityType}</span> {$opportunityTags}</h5>
        <p class="lead">{$opportunityInfo["description"]}</p>
        <p class="text-muted">Start date: {$opportunityInfo["start_date"]}</p>
        <a class="btn btn-lg btn-primary" href="listings.php?listing_ID={$opportunityInfo["opportunity_ID"]}" role="button">View listing &raquo;</a>
EOL;
        if ($i != sizeof($savedListings) - 1)
            $displayedListings .= '<hr>';
    }
    return $displayedListings;
}

function getSavedListings()
{
    $interact_ID = getUserInteractTypeID("save");
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT opportunity_ID FROM User_Interaction WHERE user_ID = ? AND user_interact_type_ID = ?";
    $stmt = $mysqlConn->prepare($sql);
    mysqli_stmt_bind_param($stmt, "ii", $_SESSION["user_ID"], $interact_ID);
    mysqli_stmt_execute($stmt);
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $savedListings = array();
        while ($row = $result->fetch_assoc()) {
            $savedListings[] = $row;
        }
        return $savedListings;
    } else {
        return false;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);

}

/*
 *
 * Add Listing Page
 *
 */

function addListing($title, $description, $url, $startDate, $listingType)
{
    $interact_ID = getUserInteractTypeID("create");
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    if (!getOpportunityTypeFromID($listingType))
        die('<div class="alert alert-danger" role="alert">Error: Potential security violation</div>');
    $sql = "INSERT INTO Opportunity (title, description, start_date, URL, opp_type_ID, approved) VALUES (?, ?, ?, ?, ?, 0)";
    if ($stmt = mysqli_prepare($mysqlConn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssssi", $title, $description, $startDate, $url, $listingType);
        mysqli_stmt_execute($stmt);
        $opportunity_ID = $mysqlConn->insert_id;
        echo '<div class="alert alert-success" role="alert">Listing successfully submitted!</div>';
    } else {
        echo '<div class="alert alert-danger" role="alert">An error occurred.</div>';
    }
    $sql = "INSERT INTO User_Interaction(opportunity_ID, user_ID, user_interact_type_ID) VALUES (?,?,?)";
    $stmt3 = $mysqlConn->prepare($sql);
    $stmt3->bind_param("iii", $opportunity_ID, $_SESSION["user_ID"], $interact_ID);
    $stmt3->execute();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
}


/*
 *
 *  Listings Page
 *
 */

function displayAllListings()
{
    $mysqlConn = startMySQL();
    $isSearchQuery = false;
    if ($mysqlConn === false) {
        die("ERROR");
    }
    if (!empty($_POST["searchquery"])) {
        $isSearchQuery = true;
        $searchQuery = '%' . $_POST["searchquery"] . '%';
        $sql = "SELECT * FROM Opportunity WHERE CONCAT(description, title) LIKE ? AND approved = 1";
        $stmt = $mysqlConn->prepare($sql);
        $stmt->bind_param("s", $searchQuery);
    } else {
        $sql = "SELECT * FROM Opportunity WHERE approved = 1";
        $stmt = $mysqlConn->prepare($sql);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $allListings = "";
    if ($isSearchQuery)
        $allListings .= '<div class="alert alert-success mt-2" role="alert">Returning search results for: ' . htmlspecialchars($_POST["searchquery"]) . '</div>';
    for ($i = 0; $i < $result->num_rows; $i++) {
        $opportunityInfo = $result->fetch_assoc();
        htmlspecialcharsArray($opportunityInfo);
        $opportunityType = strtoupper(getOpportunityTypeFromID($opportunityInfo["opp_type_ID"]));
        $opportunityTags = "";
        $ratingStars = getRatingStars($opportunityInfo["opp_rating"]);
        if (getTagsOnOpportunity($opportunityInfo["opportunity_ID"])) {
            foreach ((array)getTagsOnOpportunity($opportunityInfo["opportunity_ID"]) as $tag) {
                if (!getTagDescription($tag["opp_tag_join_ID"]))
                    continue;
                $opportunityTags .= '<span class="badge bg-secondary me-1">' . getTagDescription($tag["opp_tag_join_ID"]) . '</span>';
            }
        }
        $allListings .= <<<EOL
<h1>{$opportunityInfo["title"]} <small>$ratingStars</small></h1>
        <h5><span class="badge bg-secondary me-1">{$opportunityType}</span> {$opportunityTags}</h5>
        <p class="lead">{$opportunityInfo["description"]}</p>
        <p class="text-muted">Start date: {$opportunityInfo["start_date"]}</p>
        <a class="btn btn-lg btn-primary" href="listings.php?listing_ID={$opportunityInfo["opportunity_ID"]}" role="button">View listing &raquo;</a>
EOL;
        if ($i != $result->num_rows - 1)
            $allListings .= '<hr>';

    }


    echo <<<EOL
    <main class="container mb-2">
    <div class="bg-body-tertiary p-5 rounded">
    <form method="POST">
                            <div class="search mb-3">
                          <i class="fa fa-search"></i>
                          <input type="text" class="form-control" name="searchquery" placeholder="Search Listings">
                          <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                        </form>
                        
    {$allListings}
    </div>
    </main>
    EOL;

    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);

}

function displayListing($listingID)
{
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT * FROM Opportunity WHERE opportunity_ID = ? AND approved = 1";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $listingID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $opportunityInfo = $result->fetch_assoc();
        htmlspecialcharsArray($opportunityInfo);
        $opportunityType = strtoupper(getOpportunityTypeFromID($opportunityInfo["opp_type_ID"]));
        if (isListingSaved($opportunityInfo["opportunity_ID"]))
            $saveStatus = "Unsave";
        else
            $saveStatus = "Save";
        $opportunityTags = "";
        $opportunityComments = "";
        $ratingStars = getRatingStars($opportunityInfo["opp_rating"]);
        if (getTagsOnOpportunity($opportunityInfo["opportunity_ID"])) {
            foreach ((array)getTagsOnOpportunity($opportunityInfo["opportunity_ID"]) as $tag) {
                if (!getTagDescription($tag["opp_tag_join_ID"]))
                    continue;
                $opportunityTags .= '<span class="badge bg-secondary me-1">' . getTagDescription($tag["opp_tag_join_ID"]) . '</span>';
            }
        }
        foreach (getOpportunityComments($opportunityInfo["opportunity_ID"]) as $comment) {
            $commentContent = htmlspecialchars($comment["comment"]);
            $commentOwner = getNameFromUserID($comment["user_ID"]);
            htmlspecialcharsArray($commentOwner);
            $opportunityComments .= <<<EOL
<div class="card">
  <div class="card-body">
    <blockquote class="blockquote mb-0">
      <p>{$commentContent}</p>
      <footer class="blockquote-footer">{$commentOwner["first_name"]} {$commentOwner["last_name"]}</footer>
    </blockquote>
  </div>
</div>
EOL;

        }
        if (isLoggedIn()) {
            $opportunityComments .= <<<EOL
    <hr><form method="POST">
<div class="form-floating">
    <p class="lead mb-2">Add comment</p>
    <div class="mt-3">
    <label for="comment" class="form-label">Comment</label>
    <textarea class="form-control" aria-label="Add comment" id="comment" name="comment" rows="6"></textarea>
</div></div>
<button class="btn btn btn-outline-primary mt-1" type="submit">Add comment</button>
</form>
EOL;
            $addRating = <<<EOL
<hr>
<p class="lead">Add rating</p>
<form method="POST">
<div class="star-rating">
    <input id="star-5" type="radio" name="rating" value="5">
    <label for="star-5" title="5 stars">
        <i class="active fa fa-star" ></i>
    </label>
    <input id="star-4" type="radio" name="rating" value="4">
    <label for="star-4" title="4 stars">
        <i class="active fa fa-star" ></i>
    </label>
    <input id="star-3" type="radio" name="rating" value="3">
    <label for="star-3" title="3 stars">
        <i class="active fa fa-star" ></i>
    </label>
    <input id="star-2" type="radio" name="rating" value="2">
    <label for="star-2" title="2 stars">
        <i class="active fa fa-star" ></i>
    </label>
    <input id="star-1" type="radio" name="rating" value="1">
    <label for="star-1" title="1 star">
        <i class="active fa fa-star "></i>
  </div>
  <button class="btn btn btn-outline-primary mt-1" type="submit">Add rating</button>
</form>

EOL;

            $saveListing = <<<EOL
<form method="POST">
  <input type="hidden" name="savelisting" value="{$opportunityInfo["opportunity_ID"]}">
  <button class="btn btn-lg btn-success mt-1" type="submit">$saveStatus listing</button>
</form>

EOL;

        }


    } else {
        header('Location: listings.php');
        die();
    }
    echo <<<EOL
    <main class="container">
    <div class="bg-body-tertiary p-5 rounded">
        <h1>{$opportunityInfo["title"]} <small><span class="badge bg-secondary">{$opportunityType}</span></small></h1>
        <h5>{$opportunityTags}</h5>
        <p class="lead">{$opportunityInfo["description"]}</p>
        <p class="text-muted">Start date: {$opportunityInfo["start_date"]}</p>
        <p>$ratingStars</p>
        <a class="btn btn-lg btn-primary" href="http://{$opportunityInfo["URL"]}" role="button">Apply on Website &raquo;</a>
        {$saveListing}
        <hr>
        <p class="lead">Comments</p>
        {$opportunityComments}
        {$addRating}
        <hr>
        <a class="w-100 btn btn-danger" href="listings.php">View all listings</a>
    </div>
    </main>
    EOL;

    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
}

function getRatingStars($opportunityRating)
{
    if (is_null($opportunityRating) || $opportunityRating < 0)
        return str_repeat('<span class="fa fa-star"></span>', 5);
    $opportunityRating = round($opportunityRating);
    $ratingStars = str_repeat('<span class="fa fa-star checked-star"></span>', $opportunityRating);
    if ($opportunityRating < 5) {
        $ratingStars .= str_repeat('<span class="fa fa-star"></span>', 5 - $opportunityRating);
    }
    return $ratingStars;
}

function toggleListingSave($opportunity_ID)
{
    $mysqlConn = startMySQL();
    $interact_ID = getUserInteractTypeID("save");
    if ($mysqlConn === false) {
        die("ERROR");
    }
    if (isListingSaved($opportunity_ID)) {
        $sql = "DELETE FROM User_Interaction WHERE opportunity_ID=? AND user_ID=? AND user_interact_type_ID = ?";
        $stmt = $mysqlConn->prepare($sql);
        $stmt->bind_param("iii", $opportunity_ID, $_SESSION["user_ID"], $interact_ID);
    } else {
        $query = "INSERT INTO User_Interaction (opportunity_ID, user_ID, user_interact_type_ID) VALUES (?, ?, ?)";
        $stmt = $mysqlConn->prepare($query);
        $stmt->bind_param("iii", $opportunity_ID, $_SESSION["user_ID"], $interact_ID);
    }
    $stmt->execute();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    header('Location: listings.php?listing_ID=' . $opportunity_ID);
    die();
}

function isListingSaved($opportunity_ID)
{
    $interact_ID = getUserInteractTypeID("save");
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT user_ID FROM User_Interaction WHERE opportunity_ID=? AND user_ID=? AND user_interact_type_ID = ?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("iii", $opportunity_ID, $_SESSION["user_ID"], $interact_ID);
    $stmt->execute();
    $result = $stmt->get_result();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return ($result->num_rows == 1);
}

/*
 *
 * Comments (Listings Page)
 *
 */

function addComment($opportunity_ID, $commentContent)
{
    $interact_ID = getUserInteractTypeID("comment");
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $query = "INSERT INTO User_Interaction (opportunity_ID, user_ID, user_interact_type_ID, comment) VALUES (?, ?, ?, ?)";
    $stmt = $mysqlConn->prepare($query);
    $stmt->bind_param("iiis", $opportunity_ID, $_SESSION["user_ID"], $interact_ID, $commentContent);
    $stmt->execute();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    header('Location: listings.php?listing_ID=' . $opportunity_ID);
    die();
}


function getOpportunityComments($opportunity_ID)
{

    $interactionType = getUserInteractTypeID("comment");
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT user_ID, comment FROM User_Interaction WHERE opportunity_ID=? AND user_interact_type_ID=?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("ii", $opportunity_ID, $interactionType);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $comments = array();
        while ($row = $result->fetch_assoc()) {
            array_push($comments, $row);
        }
        return $comments;
    } else {
        return false;
    }
}


/*
 *
 * Tag System
 *
 */

function getTags()
{
    $conn = startMySQL();
    if ($conn === false) {
        die("ERROR");
    }

    $sql = "SELECT tag_ID, tag_description FROM Tags";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Output data of each row as an array of JSON objects
        $tags = array();
        while ($row = $result->fetch_assoc()) {
            array_push($tags, $row);
        }
        return $tags;
    } else {
        return false;
    }
}

function getTagsOnOpportunity($opportunity_ID)
{

    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT opp_tag_join_ID FROM Opportunity_Tag_Join WHERE opportunity_ID=?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $opportunity_ID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $tags = array();
        while ($row = $result->fetch_assoc()) {
            array_push($tags, $row);
        }
        return $tags;
    } else {
        return false;
    }
}

function getTagDescription($tag_ID)
{

    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT tag_description FROM Tags WHERE tag_ID = ?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $tag_ID);
    $stmt->execute();
    $result = $stmt->get_result();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    if ($result->num_rows == 0)
        return false;
    return $result->fetch_assoc()["tag_description"];
}

function getTagID($tag_description)
{

    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT * FROM Tags WHERE tag_description = ?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("s", $tag_description);
    $stmt->execute();
    $result = $stmt->get_result();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return $result->fetch_assoc()["tag_ID"];
}

function verifyTagNotAlreadyApplied($tagDescription, $opportunity_ID)
{

    $tag_ID = getTagID($tagDescription);
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT opp_tag_join_ID FROM Opportunity_Tag_Join WHERE opportunity_ID=? AND tag_ID=?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("ii", $opportunity_ID, $tag_ID);
    $stmt->execute();
    $result = $stmt->get_result();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return ($result->num_rows == 0);
}


function addTagToOpportunity($tagDescription, $opportunity_ID)
{

    $mysqlConn = startMySQL();
    $tag_ID = getTagID($tagDescription);
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "INSERT INTO Opportunity_Tag_Join (tag_ID, opportunity_ID) VALUES (?, ?)";
    if ($stmt = mysqli_prepare($mysqlConn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $tag_ID, $opportunity_ID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($mysqlConn);
        return true;
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($mysqlConn);
        return false;
    }


}

function verifyTagID($tag_description)
{

    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT tag_ID FROM Tags WHERE tag_description = ?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("s", $tag_description);
    $stmt->execute();
    $result = $stmt->get_result();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return ($result->num_rows == 1);
}

/*
 *
 * Sign up and Log in
 *
 */


function createAccount($emailAddress, $password, $firstName, $lastName, $userType)
{
    $mysqlConn = startMySQL();
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "INSERT INTO User (email, password, first_name, last_name, usertype_ID) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($mysqlConn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssssi", $emailAddress, $hashedPassword, $firstName, $lastName, $userType);
        mysqli_stmt_execute($stmt);
        return true;
    } else {
        return false;
    }
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
}


function preliminaryLoginCheck($email, $password): bool
{
    if (strlen($email) > 254 || strlen($email) < 3 || preg_match("/^[a-z0-9!#$%&'*+\\/=?^_`{|}~-]+(?:\\.[a-z0-9!#$%&'*+\\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/", $email) != 1 || strlen($password) < 8 || strlen($password) > 64) {
        return false;
    }
    return true;
}

function preliminarySignUpCheck($email, $password, $rpassword, $firstName, $lastName): string
{
    if (strlen($email) > 254 || strlen($email) < 3 || preg_match("/^[a-z0-9!#$%&'*+\\/=?^_`{|}~-]+(?:\\.[a-z0-9!#$%&'*+\\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/", $email) != 1) {
        return "Error: Email address is invalid";
    } elseif (strlen($password) < 8 || strlen($password) > 64) {
        return "Error: Password must be between 8 characters and 64 characters";
    } elseif (empty($firstName) || empty($lastName) || strlen($firstName) > 30 || strlen($lastName) > 30) {
        return "Error: First and last name must be between 1-30 characters";
    } elseif ($password != $rpassword) {
        return "Error: Passwords do not match";
    }
    return "Success";
}


function isEmailUsed($emailAddress)
{
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT user_ID FROM User WHERE email=?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("s", $emailAddress);
    $stmt->execute();
    $emailFound = (bool)$stmt->get_result()->fetch_row();

    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return $emailFound;
}

function accountLogin($emailAddress, $password)
{
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT * FROM User WHERE email=?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("s", $emailAddress);
    $stmt->execute();
    $result = $stmt->get_result();
    $retrievedAccount = $result->fetch_assoc();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    if (password_verify($password, $retrievedAccount["password"])) {
        $_SESSION["email"] = $emailAddress;
        $_SESSION["user_ID"] = $retrievedAccount["user_ID"];
        $_SESSION["first_name"] = $retrievedAccount["first_name"];
        $_SESSION["last_name"] = $retrievedAccount["last_name"];
        $_SESSION["usertype_ID"] = $retrievedAccount["usertype_ID"];
        return true;
    } else {
        return false;
    }
}


/*
 *
 * Rating Functions
 *
 */

function isListingRated($opportunity_ID)
{
    $interact_ID = getUserInteractTypeID("rating");
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT user_ID FROM User_Interaction WHERE opportunity_ID=? AND user_ID=? AND user_interact_type_ID = ?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("iii", $opportunity_ID, $_SESSION["user_ID"], $interact_ID);
    $stmt->execute();
    $result = $stmt->get_result();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return ($result->num_rows > 0);
}

function calculateNewRating($opportunity_ID)
{

    $ratingsArray = getOpportunityRatings($opportunity_ID);
    if (!$ratingsArray)
        return updateOpportunityRating($opportunity_ID, 0.0);
    $sumOfRatings = 0;
    for ($i = 0; $i < sizeof($ratingsArray); $i++) {
        $sumOfRatings += $ratingsArray[$i];

    }

    return updateOpportunityRating($opportunity_ID, round($sumOfRatings / sizeof($ratingsArray), 1));
}


function updateOpportunityRating($opportunity_ID, $updatedRating)
{

    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "UPDATE Opportunity SET opp_rating = ? WHERE opportunity_ID = ?";
    if ($stmt = mysqli_prepare($mysqlConn, $sql)) {
        mysqli_stmt_bind_param($stmt, "di", $updatedRating, $opportunity_ID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($mysqlConn);
        return "Rating added successfully";
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($mysqlConn);
        return "Error: Could not add rating";
    }
}


function getOpportunityRatings($opportunity_ID)
{
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT user_interact_ID FROM User_Interaction WHERE opportunity_ID = ? AND user_interact_type_ID = 3";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $opportunity_ID);
    $stmt->execute();
    $result = $stmt->get_result();
    $ratings = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ratings[] = getIndRating($row["user_interact_ID"]);
        }
    } else {
        return false;
    }
    return $ratings;
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
}

function getIndRating($user_interact_ID)
{


    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT user_ID, ind_rating FROM User_Interaction WHERE user_interact_ID = ? AND ind_rating IS NOT NULL";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $user_interact_ID);
    $stmt->execute();
    $result = $stmt->get_result();
    /*if ($result->num_rows == 0)
        return "Invalid user_interact_ID";*/
    $row = mysqli_fetch_assoc($result);
    return $row["ind_rating"];

    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);

}

function removeIndRating($opportunity_ID)
{
    $interact_ID = getUserInteractTypeID("rating");
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "DELETE FROM User_Interaction WHERE opportunity_ID = ? AND user_ID = ? AND user_interact_type_ID = ?";
    if ($stmt = mysqli_prepare($mysqlConn, $sql)) {
        mysqli_stmt_bind_param($stmt, "iii", $opportunity_ID, $_SESSION["user_ID"], $interact_ID);
        mysqli_stmt_execute($stmt);
        calculateNewRating($opportunity_ID);
        return true;
    } else {
        return false;
    }
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
}

function addIndRating($opportunity_ID, $ind_rating)
{
    // Code to add to User_Interaction table if the interaction is adding a rating
    if ($ind_rating > 5 || $ind_rating <= 0)
        return false;
    if (isListingRated($opportunity_ID))
        removeIndRating($opportunity_ID);
    $interact_ID = getUserInteractTypeID("rating");
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    if (isListingRated($opportunity_ID)) {
        $sql = "UPDATE User_Interaction SET ind_rating = ? WHERE opportunity_ID = ? AND user_ID = ?";
        $stmt = mysqli_prepare($mysqlConn, $sql);
        mysqli_stmt_bind_param($stmt, "dii", $ind_rating, $opportunity_ID, $_SESSION["user_ID"]);
    } else{
        $sql = "INSERT INTO User_Interaction (opportunity_ID, user_ID, user_interact_type_ID, ind_rating) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($mysqlConn, $sql);
        mysqli_stmt_bind_param($stmt, "iiid", $opportunity_ID, $_SESSION["user_ID"], $interact_ID, $ind_rating);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    calculateNewRating($opportunity_ID);
    return true;

}


/*
 *
 *  Misc Functions
 *
 */


function htmlspecialcharsArray(&$array)
{
    foreach ($array as &$value) {
        if (!is_array($value)) {
            $value = htmlspecialchars($value ?? '');
        } else {
            htmlspecialcharsArray($value);
        }
    }
}

function logoutButton(): void
{
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: /index.php");
    die();
}

function isLoggedIn(): bool
{
    return isset($_SESSION["user_ID"]);
}


function loadNavbarDropdown()
{
    if (isLoggedIn()) {
        echo <<<EOL
            <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                <li class="nav-item dropdown me-5">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Account
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="myaccount.php">My Account</a></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
            EOL;
    } else {
        echo <<<EOL
            <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                <li class="nav-item dropdown me-5">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Account Portal
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="signup.php">Signup</a></li>
                        <li><a class="dropdown-item" href="login.php">Login</a></li>
                    </ul>
                </li>
            </ul>
            EOL;
    }
}

function loadAddListingsPage()
{
    if (isLoggedIn()) {
        echo <<<EOL
            <li class="nav-item">
                    <a class="nav-link" href="addlisting.php">Add Listing</a>
                </li>
            EOL;
    }
}

function isPost()
{
    return ($_SERVER["REQUEST_METHOD"] == "POST");
}


function getNameFromUserID($user_ID)
{
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT first_name, last_name FROM User WHERE user_ID = ?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $user_ID);
    $stmt->execute();
    $result = $stmt->get_result();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    if ($result->num_rows == 1) {
        return $result->fetch_assoc();
    } else {
        return false;
    }
}

function getUserInteractTypeID($interaction_description)
{
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT user_interact_type_ID FROM User_Interaction_Type WHERE user_interact_type_description = ?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("s", $interaction_description);
    $stmt->execute();
    $result = $stmt->get_result();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return $result->fetch_assoc()["user_interact_type_ID"];
}

function getOpportunityTypeFromID($opp_type_ID)
{
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT opp_type_description FROM Opportunity_Type WHERE opp_type_ID = ?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $opp_type_ID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0)
        return false;
    else
        return $result->fetch_assoc()["opp_type_description"]; // This returns the opp_type_ID
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);

}

function getOpportunityInfo($opportunity_ID)
{
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT * FROM Opportunity WHERE opportunity_ID = ?";
    $stmt = $mysqlConn->prepare($sql);
    mysqli_stmt_bind_param($stmt, "i", $opportunity_ID);
    mysqli_stmt_execute($stmt);
    $result = $stmt->get_result();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return $result->fetch_assoc();
}

function isAdmin(): bool
{
    if (!isset($_SESSION["usertype_ID"]))
        return false;
    $mysqlConn = startMySQL();
    if ($mysqlConn === false) {
        die("ERROR");
    }
    $sql = "SELECT usertype_ID FROM User WHERE user_ID = ?";
    $stmt = $mysqlConn->prepare($sql);
    $stmt->bind_param("i", $_SESSION["user_ID"]);
    $stmt->execute();
    $result = $stmt->get_result();
    mysqli_stmt_close($stmt);
    mysqli_close($mysqlConn);
    return ($result->fetch_assoc()["usertype_ID"] == 2);
}

function loadAdminInNavbar(): void
{
    if (isAdmin()) {
        echo <<<EOL
            <li class="nav-item">
                    <a class="nav-link" href="admin.php">Admin</a>
                </li>
            EOL;
    }
}