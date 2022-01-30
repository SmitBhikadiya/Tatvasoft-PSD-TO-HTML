<?php
$usertype_loc = "#";
$base_url = Config::BASE_URL;
if (isset($_SESSION["usertype"])) {
    $usertype_loc = $_SESSION["usertype"][1];
}
?>
<header class="header">
    <nav class="navbar">
        <a class="navbar-brand" href="<?=$base_url.'?controller=Default&function=homepage'?>"><img src="static/images/nav-logo.png" alt=""></a>
        <ul class="nav-list">
            <li><a href="<?=$base_url.'?controller=Default&function=booknow'?> " class="nav-btn">Book a Cleaner</a></li>
            <li><a href="<?=$base_url.'?controller=Default&function=price'?>">Prices</a></li>
            <li><a href="#">Warranty</a></li>
            <li><a href="#">Blog</a></li>
            <li><a href="<?=$base_url.'?controller=Default&function=contact'?>">Contact Us</a></li>

            <?php if (isset($userdata)) { ?>
                <li class="nav-item notification-icon">
                    <div class="seprators">
                        <div class="n-counter">12</div>
                        <img src="static/images/icon-notification.png" alt="">
                    </div>
                </li>
                <li class="nav-item li-custom-dropdown li-dropdown">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-expanded="false">
                            <img src="static/images/admin-user.png" alt="">
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                            <div class="dropdown-item">
                                Welcome, <br><b><?= $userdata["firstname"] . " " . $userdata["lastname"] ?></b>
                            </div>
                            <hr style="margin: 5px;">
                            <a class="dropdown-item" href="<?= $usertype_loc ?>">
                                My Dashboard
                            </a>
                            <a class="dropdown-item" href="<?= $usertype_loc . '?req=setting' ?>">
                                My Setting
                            </a>
                            <a class="dropdown-item" href="./controllers/UsersController.php?lg=logout">
                                Logout
                            </a>
                        </div>
                    </div>
                </li><?php
                    } else {
                        ?>
                <li><a href="Homepage.php?login=m" class="nav-btn">Login</a></li>
                <li><a href="sp-sign-up.php" class="nav-btn">Become a Helper</a></li>
            <?php
                    } ?>

        </ul>
        <div class="menu-bar">
            <i class="fa fa-bars"></i>
        </div>
    </nav>
</header>