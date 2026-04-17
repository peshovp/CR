<?php require_once 'session_check.php'; ?>
<!-- Navigation-->
<nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="index.php">
          <img src="./img/logo_navbar_letter.png" width="185" height="30" alt="">
        </a>
    </div>
    <!-- /.navbar-header -->
        
    <ul class="nav navbar-top-links navbar-right">
        
        <li>
            <a href="index.php"><i class="fa fa-user fa-fw"></i> Hello <?php echo isset( $_SESSION['username'] )? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') : "&#60;username&#62;";?></a>
            
        </li>
        <!-- Sign out -->
        <li>
            <a href="logout.php"><i class="fa fa-sign-out fa-fw"></i> Sign out</a>
        </li>
    </ul>
    <!-- /.navbar-top-links -->


    <!--  Sidebar Menu  -->
    <div class="navbar-default sidebar" role="navigation">
        <div class="sidebar-nav navbar-collapse">
            <ul class="nav" id="side-menu">
                <li>
                    <a href="./index.php" style="text-align:center;"><img src="./img/logo200px.png" alt="Logo Avatar" width=100 height=100></a>
                    
                </li>
                <!-- Dashboard -->
                <li>
                    <a href="./index.php"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
                </li>
                
                <!-- Streams -->
                <li>
                    <a href="#"><i class="fa fa-tasks fa-fw"></i> Streams<span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level">
                        <li>
                            <a href="./newStream.php"><i class="fa fa-plus-square-o fa-fw"></i> New stream</a>
                        </li>
                        <li >
                            <a href="./editStreams.php"><i class="fa fa-edit fa-fw"></i> Edit streams</a>
                        </li>
                    </ul>
                    <!-- /.nav-second-level -->
                </li>
                
                <!-- Users -->
                <li>
                    <a href="#"><i class="fa fa-users fa-fw"></i> Users<span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level">
                        <li>
                            <a href="./newUser.php"><i class="fa fa-plus-square-o fa-fw"></i> New user</a>
                        </li>
                        <li>
                            <a href="./editUsers.php"><i class="fa fa-edit fa-fw"></i> Edit users</a>
                        </li>
                    </ul>
                    <!-- /.nav-second-level -->
                </li>
                
                <li>
                    <a href="./map.php"><i class="fa fa-map fa-fw"></i> Live map</a>
                </li>
                
            </ul>
        </div>
        <!-- /.sidebar-collapse -->
    </div>
    <!-- /.navbar-static-side -->
</nav>