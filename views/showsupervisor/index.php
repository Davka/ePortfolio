<head>
  <meta charset="utf-8"/><meta charset="utf-8"/>

  <!-- Latest compiled and minified CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
  <!-- Latest compiled and minified JavaScript -->
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

  <style media="screen">

    .widget-list, .widget-links li {
      position: relative;
    }

    .active-link {
      background-color: #a9b6cb;
      box-shadow: inset 0 0 0 1px #7e92b0;
      color: #fff!important;
    }

    .active-link::before {
      border: 10px solid rgba(126,146,176,0);
      content: "";
      height: 0;
      width: 0;
      position: absolute;
      border-left-color: #7e92b0;
      left: 100%;
      top:50%;
      margin-top: -10px;
    }

    .legend {
      background: rgba(0, 0, 0, 0.01);
      border: 1px solid rgba(0, 0, 0, 0.2);
      max-width: 800px;
      padding: 5px 10px;
      margin-top: 30px;
    }

    .legend ul li {
      margin: 0px;
      list-style: none;
    }

    .legend ul {
      padding:0px;
    }

    td, th {
      text-align: center;
    }

  </style>

</head>

<!-- <button type="button" class="btn btn-default" data-toggle="modal" data-target="#myModal">
 Neue Supervisionsgruppe erstellen
</button> -->

<h1><?php echo showsupervisorcontroller::getCourseName($id); ?></h1>

<?php showsupervisorcontroller::getTemplates($id); ?>

<div>

  <label for="template">Template hinzufuegen</label>
  <select class="" id="tempselector" name="template">
    <?php  $templates = showsupervisorcontroller::getTemplates($id); ?>
    <?php foreach ($templates as $key => $value):?>
      <option value="<?php echo $value[id] ?>"><?php echo $value[temp_name] ?></option>
    <?php endforeach; ?>
  </select>
  <button type="button" onclick="addTemp()" name="button">Add</button>



  <!-- Nav tabs -->
  <ul class="nav nav-tabs" role="tablist">
    <?php $templistid = showsupervisorcontroller::getGroupTemplates($id);?>
    <?php foreach ($templistid as $key => $value): ?>
      <?php $tempname = showsupervisorcontroller::getTemplateName($value);?>
      <li role="presentation"><a href="#<?php echo $value; ?>" aria-controls="<?php echo $value; ?>" role="tab" data-toggle="tab"><?php echo $tempname ?></a></li>
    <?php endforeach; ?>

    <!-- <li role="presentation" class="active"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Testportfolio</a></li>
    <li role="presentation"><a href="#profile" aria-controls="profile" role="tab" data-toggle="tab">Profile</a></li>
    <li role="presentation"><a href="#messages" aria-controls="messages" role="tab" data-toggle="tab">Messages</a></li>
    <li role="presentation"><a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">Settings</a></li> -->
  </ul>

  <!-- Tab panes -->


  <div class="tab-content">

    <?php $templistid = showsupervisorcontroller::getGroupTemplates($id); ?>
    <?php foreach ($templistid as $key => $value): ?>
      <?php $tempid = $value ?>
      <div role="tabpanel" class="tab-pane" id="<?php echo $value; ?>">
        <table style="margin-bottom: 30px;max-width: 600px; text-align: left!important;">
          <tr>
            <th style="width: 200px;border-bottom: 1px solid;">Name</th>
            <?php
            $q = DBManager::get()->query("SELECT chapters FROM eportfolio_templates WHERE id = '$value'")->fetchAll();
            $q = json_decode($q[0][0], true);
            foreach ($q as $key => $valueChapter): ?>
              <th style="width: 100px; border-bottom: 1px solid;"><?php print_r($valueChapter); ?></th>
            <?php endforeach; ?>
          </tr>

          <?php foreach ($groupList as $key):?>
            <tr>
              <td style="text-align: left;">
                <?php $supervisor = UserModel::getUser($key);
                $userid = $key;
                    echo $supervisor[Vorname].' '.$supervisor[Nachname];
                 ?>
              </td>

              <?php $getsemid = DBManager::get()->query("SELECT Seminar_id FROM eportfolio WHERE owner_id = '$key' AND template_id = '$tempid'")->fetchAll();
              $getsemid = $getsemid[0][0];
              ?>

              <?php $t = DBManager::get()->query("SELECT freigaben_kapitel FROM eportfolio WHERE Seminar_id = 'h965bdvaolo50gc5uk8lj7snb96c939q'")->fetchAll();
              //  print_r($t);
              $freigaben_kapitel = json_decode($t[0][0], true);
               ?>

              <?php
              //$q = DBManager::get()->query("SELECT title, id FROM mooc_blocks WHERE type = 'Chapter' AND seminar_id = 'is22plkvtlt3ms6vvuwjsrwfuwohruq9'")->fetchAll();
              $status = DBManager::get()->query("SELECT templateStatus FROM eportfolio WHERE Seminar_id = '$getsemid'")->fetchAll();
              $status = $status[0][0];

              $q = DBManager::get()->query("SELECT title, id FROM mooc_blocks WHERE type = 'Chapter' AND seminar_id = '$getsemid'")->fetchAll();
              foreach ($q as $key => $value): ?>

                <?php if ($status == 1): ?>
                  <?php $t = DBManager::get()->query("SELECT freigaben_kapitel FROM eportfolio WHERE Seminar_id = '$getsemid'")->fetchAll();
                  $freigaben_kapitel = json_decode($t[0][0], true);?>
                  <td><?php $idNew = $value[id];
                  if($freigaben_kapitel[$idNew]):?>
                    <?php $link = URLHelper::getLink("plugins.php/courseware/courseware", array('cid' => $getsemid , 'selected' => $idNew));?>
                    <a href="<?php echo $link; ?>">
                      <i class="fa fa-check" aria-hidden="true"></i>
                    </a>
                  <?php endif; ?>
                  </td>
                <?php else: ?>
                  <td>
                    N
                  </td>
                <?php endif; ?>


              <?php endforeach; ?>

            </tr>
          <?php endforeach; ?>
        </table>

        <button type="button" name="button">Template loeschen</button>


      </div>
    <?php endforeach; ?>

    <div role="tabpanel" class="tab-pane" id="profile">Nein</div>
    <div role="tabpanel" class="tab-pane" id="messages">Warum</div>
    <div role="tabpanel" class="tab-pane" id="settings">booar</div>
  </div>

</div>

<?php if(!$id): ?>

  <div class="panel panel-primary">
  <div class="panel-heading">
    Gruppen erstellen
  </div>
  <div class="panel-body">Aktuell haben Sie noch keine Gruppen erstellt. Bitte erstellen Sie zunächst ein Gruppe um mit der Verwaltung fortzufahren</div>
</div>

<?php endif; ?>

<!-- add person buton / MultiPersonSearch -->
<?php if($id) {
  //print_r($mp);
}  ?>

<!-- <?php

$suche = new SQLSearch("SELECT * FROM auth_user_md5");
print QuickSearch::get("username", $suche)
  ->render();
  ?> -->


<!-- Legende -->
<div class="legend">
  <ul>
    <li><i class="fa fa-check" aria-hidden="true"></i>  Kapitel/Implus freigeschaltet</li>
    <li><i class="fa fa-check" aria-hidden="true" style="color:orange;"></i>  Kapitel freigeschaltet und Änderungen seit ich das letzte mal reingeschaut habe</li>
    <li><i class="fa fa-file-o" aria-hidden="true"></i>  Supervisionsanliegen freischalten</li>
    <li><i class="fa fa-comments" aria-hidden="true"></i>  Resonanz gegeben</li>
  </ul>
</div>

<div id="myModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Neue Supervisionsgruppe erstellen</h4>
      </div>
      <div class="modal-body">

        <form id="createGroupForm">

          <label>
            <span class="required">Name</span>
            <input type="text" name="name" id="wizard-name" size="75" maxlength="254" value="" required="" aria-required="true" aria-invalid="true">
          </label>

        <label>
          <span>Beschreibung</span>
          <textarea name="description" id="wizard-description" cols="75" rows="4"></textarea>
        </label>

      </form>


      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onClick="createGroup();">Save changes</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div id="userInfoModel" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"></h4>
      </div>
      <div class="modal-body">

        <div id="dataOutputer">

        </div>


      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<script type="text/javascript">
$('#myModal').on('shown.bs.modal', function () {
$('#myInput').focus()
})

function createGroup(){
  $.ajax({
    type: "POST",
    url: "/studip/plugins.php/eportfolioplugin/showsupervisor?create=1",
    data: $("#createGroupForm").serialize(),
    success: function(data) {
      // alert(data);
      // var neu = $("#createGroupForm").serialize();
      var id = data;
      // console.log(id);
      window.document.location.href = "/studip/plugins.php/eportfolioplugin/showsupervisor?id="+id;
    }
  });
}

function getUserData(id){
  $.ajax({
    type: "POST",
    url: "/studip/plugins.php/eportfolioplugin/ajaxsupervisor?userId="+id,
    dataType: 'JSON',
    success: function(data) {
      $('#userInfoModel').modal('toggle');
      console.log(data);
      _.forEach(data, function(value){
        console.log(value);
      });
    }
  });
}

$('#myTabs a').click(function (e) {
  e.preventDefault()
  $(this).tab('show')
})

function addTemp(){
  const tempid = $('#tempselector').val();
  console.log(tempid);

  $.ajax({
    type: "POST",
    url: "/studip/plugins.php/eportfolioplugin/showsupervisor",
    data: {
      type: 'addTemp',
      groupid: '<?php echo $id ?>',
      tempid: tempid
    },
    success: function(data){
      console.log(data);
      if (data == "") {
        createPortfolio(tempid);
        console.log("Noooope");
      }
    }
  });
}

function createPortfolio(tempid){
  $.ajax({
    type: "POST",
    url: "/studip/plugins.php/eportfolioplugin/showsupervisor",
    data: {
      type: 'createPortfolio',
      groupid: '<?php echo $id ?>',
      tempid: tempid
    },
    success: function(data){
      console.log(data);
    }
  });
}


$(document).ready(
  function(){
    console.log("tab");
    $('li[role="presentation"]:first').addClass("active");
  }
);

</script>
