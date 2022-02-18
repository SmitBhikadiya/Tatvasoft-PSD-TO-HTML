<?php

session_start();
require("validation/booknowvalidator.php");
require("phpmailer/mail.php");
require("modals/BookNowModal.php");

class BookNowController
{
    private $data;
    private $booknowModal;
    private $validator;
    private $errors = [];
    function __construct()
    {
        $this->data = $_POST;
        $this->booknowModal = new BookNowModal($this->data);
        $this->validator = new BookNowValidator($this->data);
    }

    function CheckPostalCode()
    {
        $result = [[], []];
        $errors = $this->validator->isPostalCodeValid();
        if (!count($errors) > 0) {
            $result = $this->booknowModal->getPostalCode();
            if (count($result[1]) > 0) {
                foreach ($result[1] as $key => $val) {
                    $this->addErrors($key, $val);
                }
            }
        } else {
            foreach ($errors as $key => $val) {
                $this->addErrors($key, $val);
            }
        }
        echo json_encode(["result" => $result[0], "errors" => $this->errors]);
    }

    function UserDetailes()
    {
        $result = [[], []];
        if (isset($_SESSION["userdata"])) {

            // get user address detailes
            $errors = $this->validator->isPostalCodeValid();
            if (!count($errors) > 0) {
                $userid = $_SESSION["userdata"]["UserId"];
                $result = $this->booknowModal->getUserDetailesByUserId($userid);
                if (count($result[1]) > 0) {
                    foreach ($result[1] as $key => $val) {
                        $this->addErrors($key, $val);
                    }
                }
            } else {
                foreach ($errors as $key => $val) {
                    $this->addErrors($key, $val);
                }
            }

            // get favorite service provider if selected any one
            $fav_list = [];
            if (isset($_POST["workwithpets"])) {
                $workwitpets = trim($_POST["workwithpets"]);
                if ($workwitpets == "true") {
                    $workwitpets = 1;
                } else {
                    $workwitpets = 0;
                }
                $fav_list = $this->booknowModal->getFavoriteSP($userid, $workwitpets);
            } else {
                $this->addErrors("Fieldname", "Work with pet field name is not verified!!");
            }
        } else {
            $this->addErrors("User", "User is not signin!!");
        }


        echo json_encode(["result" => $result[0], "favlist" => $fav_list, "errors" => $this->errors]);
    }

    function addNewAddress()
    {
        $result = [[], []];
        if (isset($_SESSION["userdata"])) {
            $errors = $this->validator->isNewAddressValidate();
            if (!count($errors) > 0) {
                $userid = $_SESSION['userdata']['UserId'];
                $email = $_SESSION['userdata']['Email'];
                $result = $this->booknowModal->insertUserAddress($userid, $email);
                if (count($result[1]) > 0) {
                    foreach ($result[1] as $key => $val) {
                        $this->addErrors($key, $val);
                    }
                }
            } else {
                foreach ($errors as $key => $val) {
                    $this->addErrors($key, $val);
                }
            }
        } else {
            $this->addErrors("User", "User is not signin!!");
        }

        echo json_encode(["result" => $result[0], "errors" => $this->errors]);
    }

    public function insertServiceRequest()
    {
        $result = [[], []];
        if (isset($_SESSION["userdata"])) {
            $errors = $this->validator->isServiceRequestValidate();
            if (count($errors) > 0) {
                foreach ($errors as $key => $val) {
                    $this->addErrors($key, $val);
                }
            } else {
                $result = $this->booknowModal->insertServiceRequest();
                $mail = "No need to send message";
                if (count($result[1]) > 0) {
                    foreach ($result[1] as $key => $val) {
                        $this->addErrors($key, $val);
                    }
                } else {
                    $emails = [];
                    $body = $this->getBodyToSendMailToSPs($result[0]["ServiceRequestId"]);
                    if ($result[0]["FavoriteServicerId"] == "NULL") {
                        $servicers = $this->booknowModal->getAllServicer($result[0]["workwitpets"]);
                        if (count($servicers) > 0) {
                            foreach ($servicers as $servicer) {
                                array_push($emails, $servicer["Email"]);
                            }
                            $mail = sendmail($emails, "New Request Arrived", "$body");
                        }
                    } else if ($result[0]["ServiceRequestId"] != 0) {
                        $servicer = $this->booknowModal->getServicerByServiceRequestId($result[0]["ServiceRequestId"], $result[0]["workwitpets"]);
                        if (count($servicer) > 0) {
                            array_push($emails, $servicer["Email"]);
                            $mail = sendmail($emails, "Request is Assigned To YOU", "$body");
                        }
                    }
                }
                // [$postalcode, $cleaningstartdate, $cleaningstarttime, $cleaningworkinghour, $totalpayment, $extrahour, $workwitpets, $spid, $status, $paymentdone, $userid]
            }
        } else {
            $this->addErrors("User", "User is not signin!!");
        }
        echo json_encode(["result" => ['service' => $result[0]], "errors" => $this->errors, "mail" => $result[0]["ServiceRequestId"]]);
    }

    public function isServiceAvailable()
    {
        $result = [[], []];
        if (isset($_SESSION["userdata"])) {
            $userid = $_SESSION["userdata"]["UserId"];
            if (isset($this->data["adid"])) {
                $adid = $this->data["adid"];
                $ondate = $this->data["selecteddate"];
                $result = $this->booknowModal->isServiceAvailable($adid, $ondate, $userid);
                if (count($result[1]) > 0) {
                    foreach ($result[1] as $key => $val) {
                        $this->addErrors($key, $val);
                    }
                }
            } else {
                $this->addErrors("Invalid", "field name is not set");
            }
        } else {
            $this->addErrors("User", "User is not signin!!");
        }
        echo json_encode(["result" => $result[0], "errors" => $this->errors]);
    }

    public function getBodyToSendMailToSPs($serviceid)
    {
        $result = $this->booknowModal->getServiceRequestById($serviceid); 
        $serviceid = substr("000".$result["ServiceRequestId"], -4);
        $startdate = $result["ServiceStartDate"];
        $status = $result["Status"];
        if($status==0){ $status = "New Request"; }
        else if($status==1) { $status = "Assigned To You"; }
        $servicehourlyrate = $result["ServiceHourlyRate"];
        $totalhour = $result["ServiceHours"];
        $extrahour = $result["ExtraHours"];
        $basichour = $totalhour - $extrahour;
        $totalcost = $result["TotalCost"];
        $addressline = $result["AddressLine1"];
        $city = $result["City"];
        $postalcode = $result["PostalCode"];
        /*
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
        */
        return '
        <html>
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style rel="stylesheet">
                *{
                    font-family: "Roboto", sans-serif;
                }
                .cnt{
                    margin: 5px;
                    padding: 12px;
                    background-color:aliceblue;
                }
                .row{
                    margin-bottom: 16px;
                    align-items: center;
                }
               h4{
                   font-weight: 300;
               }
               span{
                   font-weight: 400;
               }
               .address div{
                   background-color:tomato;
                   color: white;
                   padding: 10px 12px;
               }
               .button, .title{
                   text-align: center;
               }
            </style>
        </head>
        <body>
            <div class="cnt">
                <div class="row mt-3 title">
                    <div class="col-12 display-6" style="color:#1d7a8c;">Service Request Id : <span>0001</span></div>
                </div><hr style="margin-top: 0px;">
                <div class="row">
                    <div class="col-12">Service Status:- </div>
                    <div class="col"><h4><span>'.$status.'<span></h4></div>
                </div>
                <div class="row">
                    <div class="col-12">Starting Date:- </div>
                    <div class="col"><h4><span>'.$startdate.'<span></h4></div>
                </div>
                <div class="row">
                    <div class="col-12"><h4>'.$basichour.' (basic) + '.$extrahour.' (extra) = <span class="totalhour">'.$totalhour.' Hrs. (total)</span></h4></div>
                </div>
                <div class="row">
                    <div class="col-12">Total Bill ('.$servicehourlyrate.'€ per cleaning)</div>
                    <div class="col"><h4><span class="totalbill">'.$totalcost.'€<span></h4></div>
                </div>
                <div class="row address">
                    <div class="col-12">
                        Address: '.$addressline.', '.$city.', <span>'.$postalcode.'</span>
                    </div>
                </div>
                <div class="row button">
                    <div class="col-12">
                        <a href="'.Config::BASE_URL."controller=Default&function=ServicerDashboard".'" class="btn btn-lg btn-primary">Go To Dashboard</a>
                    </div>
                </div>
            </div>
        </body>
    </html>

    ';}
        
    /*------------- Set error ------------*/
    private function addErrors($key, $val)
    {
        $this->errors[$key] = $val;
    }
}
