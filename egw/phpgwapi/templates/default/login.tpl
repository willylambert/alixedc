
  <link rel="stylesheet" href="phpgwapi/templates/idots/css/reset.css" type="text/css" media="screen" />
  <link rel="stylesheet" href="phpgwapi/templates/idots/css/style_login.css" type="text/css" media="screen" />
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
  <script type="text/javascript" src="phpgwapi/templates/idots/ks/pngfix.js"></script>

  <script type="text/javascript" src="phpgwapi/templates/idots/js/jquery.corner.js"></script>
  
<script type="text/javascript">
$(function(){
        $('.corner_20').corner('20px');
        $('.corner_10').corner('10px');
        $('.corner_10_top').corner('10px top');
        $('#form_submit').mouseover(function(){
          $(this).css("background-color","#FEBC11");
          }).mouseout(function(){
          $(this).css("background-color","#F59829");
          });
        $('.input_text').focus(function () {
          $(this).css("border","1px solid #FEBC11");
          }).blur(function () {
          $(this).css("border","1px solid #555753");
          });

});
</script>

<div id="content_login">
<div id="content_application">
  <img src="phpgwapi/templates/idots/images/alix/alix_logo.png" alt="" class="image_logo" />
  <h1 class="nom"></h1>
</div>
  <h1 class="titre">ALIX Demo Study</h1>
<div id="content_description" class="corner_10">
  <p><strong>BD ALIX Study : </strong><br />Phase II, multicenter, randomized, adaptive, double-blind,
placebo controlled study to assess safety and efficacy of ALIX in 3-25 year old patients.</p>
</div>

<div id="content_login_form" class="corner_20">
  <h1>Please enter your login and password </h1>
  <div class="contenu corner_20">

    <form name="login_form" action="{login_url}" method="post">
		<input type="hidden" name="passwd_type" value="text" />
		<input type="hidden" name="account_type" value="u" />
      <p><label>Login :</label><input class="input_text" type="text" id="form_id" name="login" value="{cookie}" /></p>
      <p><label>Password :</label><input class="input_text" type="password" id="form_pass" name="passwd" onChange="this.form.submit()" value="" /></p>
      <p><label>&nbsp;</label><input type="submit" class="input_submit" id="form_submit" name="form_submit" value="Connection" /></p>
    </form>
  </div>
  <img src="phpgwapi/templates/idots/images/alix/security_lock.png" alt="" class="icone" />
<!--
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>The study is currently under maintenance until 17:00</strong>
-->
</div>

<div id="content_login_help" class="corner_20">
  <h1>Credentials</h1>
  <div class="contenu corner_20">
    <p><strong>Forgotten password ?</strong><br /><br />Contact Business & Decision Life Sciences by e-mail at the following address svp.clinical@businessdecision.com or call the following phone number (+33) 1 81 89 02 30. </p>
  </div>
  <img src="phpgwapi/templates/idots/images/alix/security_keyandlock.png" alt="" class="icone" />
</div>

<div id="toolbar_ico" class="inactif">
  {information}
  <ul>
    <li class="first_item corner_10"><a href="#"><span class="corner_10_top"><img src="phpgwapi/templates/idots/images/alix/user_add_inactif.png" alt="" /></span><p>&nbsp;</p></a></li>
    <li class="item corner_10"><a href="#"><span class="corner_10_top"><img src="phpgwapi/templates/idots/images/alix/user_manage_inactif.png" alt="" /></span><p>&nbsp;</p></a></li>
    <li class="item corner_10"><a href="#"><span class="corner_10_top"><img src="phpgwapi/templates/idots/images/alix/piechart_inactif.png" alt="" /></span><p>&nbsp;</p></a></li>
    <li class="item corner_10"><a href="#"><span class="corner_10_top"><img src="phpgwapi/templates/idots/images/alix/folder_inactif.png" alt="" /></span><p>&nbsp;</p></a></li>
    <li class="item corner_10"><a href="#"><span class="corner_10_top"><img src="phpgwapi/templates/idots/images/alix/application_warning_inactif.png" alt="" /></span><p>&nbsp;</p></a></li>
    <li class="last_item corner_10"><a href="#"><span class="corner_10_top"><img src="phpgwapi/templates/idots/images/alix/notification_warning_inactif.png" alt="" /></span><p>&nbsp;</p></a></li>
  </ul>
</div>

</div>