<?php
/**
 * register/index.php
 * @package
 * @author Luke Docksteader
 * @copyright KidHub Inc. All Rights Reserved.
 * Created on: 2015-08-27
 * Desc: KidHub registration form
 */

define("DB_HOST", "localhost");
define("DB_DATABASE", "kidhub_www");
define("DB_USERNAME", "kidhub");
define("DB_PASSWORD", "fmdc8j3K04x9dk30dj3S");

use \Illuminate\Database\Capsule\Manager as DB;
use \Illuminate\Database\ConnectionResolver;
use \Illuminate\Database\Connectors\ConnectionFactory;
use \Illuminate\Database\MySqlConnection;

$mysql_conn = [
  'driver'    => 'mysql',
  'host'      => DB_HOST,
  'database'  => DB_DATABASE,
  'username'  => DB_USERNAME,
  'password'  => DB_PASSWORD,
  'charset'   => 'utf8',
  'collation' => 'utf8_unicode_ci',
  'prefix'    => ''
];
$db = new DB;
$db->addConnection($mysql_conn);
$db->setAsGlobal();
$db->bootEloquent();


switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET' : {

    break;
  }

  case 'POST' : {

    break;
  }

  default: {
    http_response_code(405);
    exit;
  }
}


?>

<div class="container registration">
  <div class="row">
    <div class="island col-xs-12 col-md-10 col-md-offset-1 col-lg-8 col-lg-offset-2">
      <? if(isset($_POST["process"]) && !isset($errors["general"]) && !isset($errors["clinic"]) && !isset($errors["billing"])) { ?>
        <h1><?=$app->tx("registration.heading.success")?></h1>
        <p><?=$app->tx("registration.info.thanks-for-signing-up", array("CASE_HEADING" => strtolower($currentServiceProvider->getCaseHeadingName())))?></p>
        <?
        // Notify Service Provider
//         $template = $db->fetchOneRow("SELECT * FROM cms_email_template WHERE keyName='newClinicNotify'");
        $template = $app->tx("email.new-clinic-notify");
        require_once("html2text.php");
        require_once("sendgrid/SendGrid_loader.php");
        $sendgrid = new SendGrid(SENDGRID_USER, SENDGRID_PW);
        $mail = new SendGrid\Mail();
        $mail->setFrom(NO_REPLY_EMAIL)
            ->setFromName(SYSTEM_EMAIL_NAME)
            ->setSubject($template["subject"])
            ->setTos(array($currentServiceProvider->getContact()->getEmail()))
            ->setHtml($template["content"])
            ->setText(html2text($template["content"]))
            ->addSubstitution("%SERVICE_PROVIDER%", array($currentServiceProvider->getName()))
            ->addSubstitution("%CLINICNAME%", array("$clinicName"))
            ->addSubstitution("%WEBSITE%", array(WEBSITE))
            ->addSubstitution("%LOCATION%", array((isset($provinceCode) ? $db->fetchOne("SELECT name FROM cms_province WHERE code='$provinceCode'") : $province)))
            ->addCategory(SENDGRID_CATEGORY)
            ->addCategory(SENDGRID_CATEGORY."-NewClinicNotify");
        if (!IS_LIVE) { $mail->addCategory("Test"); }
        if(SITE_EMAIL_ENABLED && sizeof($mail->getTos())) { $sendgrid->smtp->send($mail); }

        // Email {$currentServiceProvider->getClinicHeadingName()}
//         $template = $db->fetchOneRow("SELECT * FROM cms_email_template WHERE keyName='newClinicWelcome'");
        $template = $app->tx("email.new-clinic-welcome");
        require_once("html2text.php");
        require_once("sendgrid/SendGrid_loader.php");
        $sendgrid = new SendGrid(SENDGRID_USER, SENDGRID_PW);
        $mail = new SendGrid\Mail();
        $mail->setFrom(NO_REPLY_EMAIL)
            ->setFromName(SYSTEM_EMAIL_NAME)
            ->setSubject($template["subject"])
            ->setTos(array($email,$clinicEmail))
            ->setReplyTo($currentServiceProvider->getContact()->getEmail())
            ->setHtml($template["content"])
            ->setText(html2text($template["content"]))
            ->addSubstitution("%WEBSITE%", array(WEBSITE))
            ->addSubstitution("%SERVICE_PROVIDER%", array($currentServiceProvider->getName()))
            ->addSubstitution("%CLINICNAME%", array("$clinicName"))
            ->addSubstitution("%USERNAME%", array("$username"))
            ->addSubstitution("%PASSWD%", array("$passwd"))
            ->addCategory(SENDGRID_CATEGORY)
            ->addCategory(SENDGRID_CATEGORY."-NewClinicWelcome");
        if (!IS_LIVE) { $mail->addCategory("Test"); }
        if(SITE_EMAIL_ENABLED && sizeof($mail->getTos())) { $sendgrid->smtp->send($mail); }
        header("Refresh:2; URL=/login");
        echo "</div>";
      } else {
        ?>
        <h1><?=$app->tx("registration.heading.thanks-for-supporting", array("SERVICE_PROVIDER" => $currentServiceProvider->getName()))?></h1>
        <p><?= $app->tx("registration.info.please-complete-registration", array("CASE_HEADING" => strtolower($currentServiceProvider->getCaseHeadingName())))?></p>
        <?
        if (isset($_POST["process"]) && (isset($errors["general"]) || isset($errors["clinic"]) || isset($errors["billing"]))) {
          showError($app->tx("registration.error.please-see-below"));
        }
      ?>
      <form id="cms_registration" name="cms_registration" class="form-horizontal" method="post" action="" role="form">
        <fieldset>
          <? if(!$currentServiceProvider->hasSetting("generic-clinic-account")){ ?>
            <h3><?=$app->tx("registration.heading.your-info")?></h3>
            <?
            if (isset($errors["general"])) {
              foreach ($errors["general"] as $errorMsg) {
                showError($errorMsg);
              }
            }
            ?>
            <div class="form-group" id="row_existing_clinic">
              <label class="control-label col-sm-3" for="existing_clinic"><?=$app->tx("registration.field.add-user-to-existing-clinic", array("CLINIC_HEADING" => $currentServiceProvider->getClinicHeadingName()))?>:</label>
              <div class="col-sm-9">
                <div class="checkbox">
                  <input type="checkbox" name="existing_clinic" id="existing_clinic" <?=isset($exisitingClinic) ? "checked='checked'" : ""?>/>
                </div>
              </div>
            </div>
            <div class="form-group" id="row_firstName">
              <label class="control-label col-sm-3<?=isset($errors["general"]["firstName"]) ? " invalid" : ""?>" for="firstName"><?=$app->tx("user.field.first-name")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <input class="form-control" type="text" name="firstName" id="firstName" value="<?=isset($firstName) ? stripslashes($firstName) : ""?>" maxlength="100" required="required" />
              </div>
            </div>
            <div class="form-group" id="row_lastName">
              <label class="control-label col-sm-3<?=isset($errors["general"]["lastName"]) ? " invalid" : ""?>" for="lastName"><?=$app->tx("user.field.last-name")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <input class="form-control" type="text" name="lastName" id="lastName" value="<?=isset($lastName) ? stripslashes($lastName) : ""?>" maxlength="100" required="required" />
              </div>
            </div>
            <div class="form-group" id="row_email">
              <label class="control-label col-sm-3<?=isset($errors["general"]["email"]) ? " invalid" : ""?>" for="email"><?=$app->tx("user.field.email")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <input class="form-control" type="text" name="email" id="email" value="<?=isset($email) ? stripslashes($email) : ""?>" maxlength="100" required="required" />
              </div>
            </div>
            <div class="form-group" id="row_passwd">
              <label class="control-label col-sm-3<?=isset($errors["general"]["passwd"]) ? " invalid" : ""?>" for="passwd"><?=$app->tx("user.field.password")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <input class="form-control" type="password" name="passwd" id="passwd" value="" maxlength="255" required="required" />
              </div>
            </div>
            <div class="form-group" id="row_passwdc">
              <label class="control-label col-sm-3<?=isset($errors["general"]["passwd"]) ? " invalid" : ""?>" for="passwdc"><?=$app->tx("user.field.password-confirm")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <input class="form-control" type="password" name="passwdc" id="passwdc" value="" maxlength="255" required="required" />
              </div>
            </div>
            <div class="form-group" id="row_referrer">
              <label class="control-label col-sm-3<?=isset($errors["general"]["referrer"]) ? " invalid" : ""?>" for="referrer"><?=$app->tx("registration.field.referrer", array("SERVICE_PROVIDER" => $currentServiceProvider->getName()))?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <textarea class="form-control" name="referrer" id="referrer" rows="5" cols="60" required="required"><?=isset($referrer) ? stripslashes($referrer) : ""?></textarea>
              </div>
            </div>
            <? if(($currentServiceProvider->hasService("radiology")||$currentServiceProvider->hasService("diagnosticimaging")) && $currentServiceProvider->hasSetting("register-other-details")) { ?>
            <div class="form-group" id="row_otherDetails">
              <label class="control-label col-sm-3<?=isset($errors["general"]["otherDetails"]) ? " invalid" : ""?>" for="otherDetails"><?=$app->tx("registration.field.other-details")?>:<? echo ($currentServiceProvider->hasSetting("require-other-details") ? "<span class='tv-edit-view-required'>*</span>":"") ?></label>
              <div class="col-sm-9">
                <textarea class="form-control" name="otherDetails" id="otherDetails" rows="5" cols="60" <? echo ($currentServiceProvider->hasSetting("require-other-details") ? "required='required'":"") ?>><?=isset($otherDetails) ? stripslashes($otherDetails) : ""?></textarea>
              </div>
            </div>
            <? } ?>
          <? } ?>
          <? if($currentServiceProvider->getClinicHeadingName()!="Owner"){ ?>
            <h3><?=$app->tx("registration.heading.clinic-info", array("CLINIC_HEADING" => $currentServiceProvider->getClinicHeadingName()))?></h3>
          <? } ?>
          <?
          if($currentServiceProvider->hasSetting("generic-clinic-account")){
            if (isset($errors["general"])) {
              foreach ($errors["general"] as $errorMsg) {
                showError($errorMsg);
              }
            }
          }
          ?>
          <?
          if (isset($errors["clinic"])) {
            foreach ($errors["clinic"] as $errorMsg) {
              showError($errorMsg);
            }
          }
          if($currentServiceProvider->getClinicHeadingName()!="Owner"){ ?>
          <div class="form-group" id="row_institution">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["clinicName"]) ? " invalid" : ""?>" for="clinicName"><?=$app->tx("clinic.field.clinic-name", array("CLINIC_HEADING" => $currentServiceProvider->getClinicHeadingName()))?>:<span class="tv-edit-view-required">*</span></label>
            <div class="col-sm-9">
              <input class="form-control" type="text" name="clinicName" id="clinicName" value="<?=isset($clinicName) ? stripslashes($clinicName) : ""?>" maxlength="255" required="required" />
            </div>
          </div>
          <div class="form-group" id="row_practiceType">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["practiceType"]) ? " invalid" : ""?>" for="practiceType"><?=$app->tx("clinic.field.practice-type")?>:<span class="tv-edit-view-required">*</span></label>
            <div class="col-sm-9">
              <select name="practiceType" id="practiceType" class="form-control" required="required">
                <option value="Companion"<?=(isset($practiceType) ? ($practiceType == "Companion" ? " selected" : "") : " selected")?>><?=$app->tx("clinic.field.practice-type-options.companion")?></option>
                <option value="Mixed"<?=(isset($practiceType) ? ($practiceType == "Mixed" ? " selected" : "") : "")?>><?=$app->tx("clinic.field.practice-type-options.mixed")?></option>
              </select>
            </div>
          </div>
          <div class="form-group" id="row_clinicEmail">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["email"]) ? " invalid" : ""?>" for="clinicEmail"><?=$app->tx("clinic.field.admin-email")?>:<span class="tv-edit-view-required">*</span></label>
            <div class="col-sm-9">
              <input class="form-control" type="text" name="clinicEmail" id="clinicEmail" value="<?=isset($clinicEmail) ? stripslashes($clinicEmail) : ""?>" maxlength="100" required="required" />
            </div>
          </div>
          <div class="form-group" id="row_clinicURL">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["url"]) ? " invalid" : ""?>" for="url"><?=$app->tx("clinic.field.website")?>:</label>
            <div class="col-sm-9">
              <input class="form-control" type="text" name="clinicURL" id="clinicURL" value="<?=isset($clinicURL) ? stripslashes($clinicURL) : ""?>" maxlength="100" />
            </div>
          </div>
          <? } ?>
          <div class="form-group" id="row_clinic_localPhone">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["clinicLocalPhone"]) ? " invalid" : ""?>" for="clinicLocalPhone"><?=$app->tx("clinic.field.local-phone")?>:<span class="tv-edit-view-required">*</span></label>
            <div class="col-sm-9">
              <input class="form-control" type="text" name="clinicLocalPhone" id="clinicLocalPhone" value="<?=isset($clinicLocalPhone) ? stripslashes($clinicLocalPhone) : ""?>" maxlength="20" required="required" />
            </div>
          </div>
          <div class="form-group" id="row_clinic_mobilePhone">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["clinicMobilePhone"]) ? " invalid" : ""?>" for="clinicMobilePhone"><?=$app->tx("clinic.field.mobile-phone")?>:</label>
            <div class="col-sm-9">
              <input class="form-control" type="text" name="clinicMobilePhone" id="clinicMobilePhone" value="<?=isset($clinicMobilePhone) ? stripslashes($clinicMobilePhone) : ""?>" maxlength="20" />
            </div>
          </div>
          <div class="form-group" id="row_clinic_tollfreePhone">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["clinicTollFreePhone"]) ? " invalid" : ""?>" for="clinicTollFreePhone"><?=$app->tx("clinic.field.toll-free-phone")?>:</label>
            <div class="col-sm-9">
              <input class="form-control" type="text" name="clinicTollFreePhone" id="clinicTollFreePhone" value="<?=isset($clinicTollFreePhone) ? stripslashes($clinicTollFreePhone) : ""?>" maxlength="20" />
            </div>
          </div>
          <div class="form-group" id="row_clinic_fax">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["clinicFax"]) ? " invalid" : ""?>" for="clinicFax"><?=$app->tx("clinic.field.fax")?>:</label>
            <div class="col-sm-9">
              <input class="form-control" type="text" name="clinicFax" id="clinicFax" value="<?=isset($clinicFax) ? stripslashes($clinicFax) : ""?>" maxlength="20" />
            </div>
          </div>
          <div class="form-group" id="row_address1">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["address1"]) ? " invalid" : ""?>" for="address1"><?=$app->tx("clinic.field.address1")?>:<span class="tv-edit-view-required">*</span></label>
            <div class="col-sm-9">
              <input class="form-control" type="text" name="address1" id="address1" value="<?=isset($address1) ? stripslashes($address1) : ""?>" maxlength="255" required="required" />
            </div>
          </div>
          <div class="form-group" id="row_address2">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["address2"]) ? " invalid" : ""?>" for="address2"><?=$app->tx("clinic.field.address2")?>:</label>
            <div class="col-sm-9">
              <input class="form-control" type="text" name="address2" id="address2" value="<?=isset($address2) ? stripslashes($address2) : ""?>" maxlength="255" />
            </div>
          </div>
          <div class="form-group" id="row_city">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["city"]) ? " invalid" : ""?>" for="city"><?=$app->tx("clinic.field.city")?>:<span class="tv-edit-view-required">*</span></label>
            <div class="col-sm-9">
              <input class="form-control" type="text" name="city" id="city" value="<?=isset($city) ? stripslashes($city) : ($currentServiceProvider->getClinicHeadingName()=="Owner" ? $currentServiceProvider->getContact()->getCity():"")?>" maxlength="50" required="required" />
            </div>
          </div>
          <div class="form-group" id="row_province_code">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["province"]) ? " invalid" : ""?>" for="provinceCode"><?=$app->tx("clinic.field.province")?>*:</label>
            <div class="col-sm-9">
              <select name="provinceCode" id="provinceCode" class="form-control" required='required' />
                <option></option>
                <?
                $selProvinceCode = isset($provinceCode) ? $provinceCode : $currentServiceProvider->getContact()->getProvinceCode();
                foreach ($db->iterate("SELECT * FROM cms_province ORDER BY provinceOrder") as $provinceCode) {
                  echo "<option value='$provinceCode[code]'".($selProvinceCode == $provinceCode["code"] ? " selected" : "").">$provinceCode[name]</option>";
                }
                ?>
              </select>
            </div>
          </div>
          <?if(false){?>
          <div class="form-group" id="row_province">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["province"]) ? " invalid" : ""?>" for="province"><?=$app->tx("clinic.field.province-other")?>:</label>
            <div class="col-sm-9">
              <input class="form-control" type="text" name="province" id="province" value="<?=isset($province) ? $province : ""?>" maxlength="50" />
            </div>
          </div>
          <?}?>
          <div class="form-group" id="row_country">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["country"]) ? " invalid" : ""?>" for="country"><?=$app->tx("clinic.field.country")?>:<span class="tv-edit-view-required">*</span></label>
            <div class="col-sm-9">
              <select name="country" id="country" class="form-control" required="required" />
                <option></option>
                <?
                $selCountry = isset($country) ? $country : $currentServiceProvider->getContact()->getCountryCode();
                foreach ($db->iterate("SELECT * FROM cms_country ORDER BY countryOrder DESC, name") as $country) {
                  echo "<option value='$country[code]'".($selCountry == $country["code"] ? " selected" : "").">$country[name]</option>";
                }
                ?>
              </select>
            </div>
          </div>
          <div class="form-group" id="row_postal">
            <label class="control-label col-sm-3<?=isset($errors["clinic"]["postal"]) ? " invalid" : ""?>" for="postal"><?=$app->tx("clinic.field.postal")?>:<span class="tv-edit-view-required">*</span></label>
            <div class="col-sm-9">
              <input class="form-control" type="text" name="postal" id="postal" value="<?=isset($postal) ? stripslashes($postal) : ""?>" maxlength="10" required="required" />
            </div>
          </div>
          <? if($currentServiceProvider->hasSetting("generic-clinic-account")){ ?>
            <div class="form-group" id="row_referrer">
              <label class="control-label col-sm-3<?=isset($errors["general"]["referrer"]) ? " invalid" : ""?>" for="referrer"><?=$app->tx("registration.field.referrer", array("SERVICE_PROVIDER" => $currentServiceProvider->getName()))?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <textarea class="form-control" name="referrer" id="referrer" rows="5" cols="60" required="required"><?=isset($referrer) ? stripslashes($referrer) : ""?></textarea>
              </div>
            </div>
            <? if(($currentServiceProvider->hasService("radiology")||$currentServiceProvider->hasService("diagnosticimaging")) && $currentServiceProvider->hasSetting("register-other-details")) { ?>
            <div class="form-group" id="row_otherDetails">
              <label class="control-label col-sm-3<?=isset($errors["general"]["otherDetails"]) ? " invalid" : ""?>" for="otherDetails"><?=$app->tx("registration.field.other-details")?>:<? echo ($currentServiceProvider->hasSetting("require-other-details") ? "<span class='tv-edit-view-required'>*</span>":"") ?></label>
              <div class="col-sm-9">
                <textarea class="form-control" name="otherDetails" id="otherDetails" rows="5" cols="60" <? echo ($currentServiceProvider->hasSetting("require-other-details") ? "required='required'":"") ?>><?=isset($otherDetails) ? stripslashes($otherDetails) : ""?></textarea>
              </div>
            </div>
            <? } ?>
            <div class="form-group" id="row_passwd">
              <label class="control-label col-sm-3<?=isset($errors["general"]["passwd"]) ? " invalid" : ""?>" for="passwd"><?=$app->tx("user.field.password")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <input class="form-control" type="password" name="passwd" id="passwd" value="" maxlength="255" required="required" />
              </div>
            </div>
            <div class="form-group" id="row_passwdc">
              <label class="control-label col-sm-3<?=isset($errors["general"]["passwd"]) ? " invalid" : ""?>" for="passwdc"><?=$app->tx("user.field.password-confirm")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <input class="form-control" type="password" name="passwdc" id="passwdc" value="" maxlength="255" required="required" />
              </div>
            </div>
            <? showSuccess($app->tx("registration.info.username-will-be-emailed-upon-activation")); ?>
          <? } ?>
          <? if(STRIPE_IS_ACTIVE && $currentServiceProvider->hasSetting("stored-cc-payment")){ ?>
          <h3 id="billing_header"><?=$app->tx("registration.heading.billing-info")?></h3>
          <?
            showSuccess($app->tx("credit-card.info.automated-billing"), false, false, "billing_alert");
            echo "<div id='billing_errors'>";
            if (isset($errors["billing"])) {
              foreach ($errors["billing"] as $errorMsg) {
                showError($errorMsg);
              }
            }
            echo "</div>";
          if(!$currentServiceProvider->hasSetting("credit-card-mandatory")) {
          ?>
          <div class="form-group" id="row_billing_decline">
            <label class="control-label col-sm-3" for="billing_decline"><?=$app->tx("credit-card.field.decline-automated-billing")?>:</label>
            <div class="col-sm-9">
              <div class="checkbox">
                <input type="checkbox" name="billing_decline" id="billing_decline" />
              </div>
            </div>
          </div>
          <? } ?>
          <div id="billing">
            <div class="form-group" id="row_card_holder_name">
              <label class="control-label col-sm-3<?=isset($errors["billing"]["cardHolderName"]) ? " invalid" : ""?>" for="card_holder_name"><?=$app->tx("credit-card.field.card-holder-name")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <input class="form-control" type="text" name="card_holder_name" id="card_holder_name" value="" maxlength="255" required="required" />
              </div>
            </div>
            <div class="form-group" id="row_card_number">
              <label class="control-label col-sm-3<?=isset($errors["billing"]["cardNumber"]) ? " invalid" : ""?>" for="card_number"><?=$app->tx("credit-card.field.card-number")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <input class="form-control" type="text" name="card_number" id="card_number" value="" maxlength="50" required="required" />
              </div>
            </div>
            <div class="form-group" id="row_cvc">
              <label class="control-label col-sm-3<?=isset($errors["billing"]["cvc"]) ? " invalid" : ""?>" for="cvc"><?=$app->tx("credit-card.field.cvc")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <input class="form-control" type="text" name="cvc" id="cvc" value="" maxlength="4" required="required" />
              </div>
            </div>
            <div class="form-group" id="row_exp_month">
              <label class="control-label col-sm-3<?=isset($errors["billing"]["exp_month"]) ? " invalid" : ""?>" for="exp_month"><?=$app->tx("credit-card.field.expiry-month")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <select name="exp_month" id="exp_month" class="form-control" required="required">
                  <?
                  $currentMonth = date("n");
                  for ($month=1; $month<=12 ; $month++) {
                    echo "<option value='$month' ".($currentMonth==$month?"selected='selected'":"").">".  str_pad($month, 2, "0", STR_PAD_LEFT)."</option>";
                  }
                  ?>
                </select>
              </div>
            </div>
            <div class="form-group" id="row_card_exp_year">
              <label class="control-label col-sm-3<?=isset($errors["billing"]["exp_year"]) ? " invalid" : ""?>" for="exp_year"><?=$app->tx("credit-card.field.expiry-year")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <select name="exp_year" id="exp_year" class="form-control" required="required">
                  <?
                  $currentYear = date("Y")+1;
                  for ($i=-1; $i<10 ; $i++) {
                    $year = $currentYear + $i;
                    echo "<option ".($year==$currentYear ? "selected='selected'":"")." value='$year'>$year</option>";
                  }
                  ?>
                </select>
              </div>
            </div>
            <h4 style="margin-top:25px; margin-bottom:10px;"><?=$app->tx("registration.heading.billing-address")?></h4>
            <?
              showWarning($app->tx("credit-card.warning.enter-full-billing-address"));
            ?>
            <!--Street 1-->
            <div class="form-group<?=isset($errors["billing"]["address_line1"]) ? " error" : ""?>">
              <label for="address_line1" class="control-label col-sm-3"><?=$app->tx("credit-card.field.address1")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9"><input name="address_line1" type="text" value="<? /*=$address_line1*/ ?>" id="address_line1" class='form-control' required="required" /></div>
            </div>
            <!--Street 2-->
            <div class="form-group<?=isset($errors["billing"]["address_line2"]) ? " error" : ""?>">
              <label for="address_line2" class="control-label col-sm-3"><?=$app->tx("credit-card.field.address2")?>:</label>
              <div class="col-sm-9"><input name="address_line2" type="text" value="<? /*=$address_line2*/ ?>" id="address_line2" class='form-control' /></div>
            </div>
            <!--City-->
            <div class="form-group<?=isset($errors["billing"]["address_city"]) ? " error" : ""?>">
              <label for="address_city" class="control-label col-sm-3"><?=$app->tx("credit-card.field.city")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9"><input name="address_city" type="text" value="<? /*=$address_city*/ ?>" id="address_city" class='form-control' required="required" /></div>
            </div>
            <!--Province-->
            <div class="form-group<?=isset($errors["billing"]["address_state"]) ? " error" : ""?>">
              <label for="address_state" class="control-label col-sm-3"><?=$app->tx("credit-card.field.province")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <select id="address_state" name="address_state" class='form-control' required="required">
                  <option value=""></option>
                  <?
                  $address_province = isset($address_province) ? $address_province : $currentServiceProvider->getContact()->getProvinceCode();
                  foreach ($db->iterate("SELECT cms_province.* FROM cms_province ORDER BY provinceOrder") as $province) {
                    echo "<option value='$province[code]'".(isset($address_province) && $province["code"] == $address_province ? " selected" : "").">$province[name]</option>";
                  }
                  ?>
                </select>
              </div>
            </div>
            <!--Country-->
            <div class="form-group<?=isset($errors["billing"]["address_country"]) ? " error" : ""?>">
              <label for="address_country" class="control-label col-sm-3"><?=$app->tx("credit-card.field.country")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9">
                <select id="address_country" name="address_country" class='form-control' required="required">
                  <option value=""></option>
                  <?
                  $address_country = isset($address_country) ? $address_country : $currentServiceProvider->getContact()->getCountryCode();
                  foreach ($db->iterate("SELECT * FROM cms_country ORDER BY countryOrder DESC, name") as $country) {
                    echo "<option value='$country[code]'".(isset($address_country) && $country["code"] == $address_country ? " selected" : "").">$country[name]</option>";
                  }
                  ?>
                </select>
              </div>
            </div>
            <!--Postal-->
            <div class="form-group<?=isset($errors["billing"]["address_zip"]) ? " error" : ""?>">
              <label for="address_zip" class="control-label col-sm-3"><?=$app->tx("credit-card.field.postal")?>:<span class="tv-edit-view-required">*</span></label>
              <div class="col-sm-9"><input name="address_zip" type="text" value="<? /*=$address_zip*/ ?>" id="address_zip" class='form-control' required="required" /></div>
            </div>
          </div>
          <? } ?>
        </fieldset>
        <div class="form-group">
          <div class="tv-buttons col-sm-12 center-block text-center">
            <button id="cms_registration_save_button" class="btn btn-lg btn-success" type="submit" name="process" value="Register"><?=$app->tx("registration.button.register")?></button>
            <? if(empty($_POST["process"]) && empty($errors)) {
              if(STRIPE_IS_ACTIVE && $currentServiceProvider->hasSetting("stored-cc-payment")) { ?>
                <a href="http://stripe.com" target="_blank" alt="Powered by Stripe"><img class="img-responsive center-block" src="<?=WEBSITE?>img/stripe/stripe3.png" width="119px" /></a>
              <? }
             } ?>
          </div>
        </div>
      </form>
      <hr />
      <a href="http://www.timelessveterinary.com" target="_blank" alt="Timeless Veterinary Systems Inc."><img src="<?=WEBSITE?>img/logo/tvs-md.png" class="img-responsive center-block"/></a>
      <div class="text-center text-muted">
        <?=COPYRIGHT?><br />
      </div>
      <?}?>
    </div>
  </div>
</div>

<?
