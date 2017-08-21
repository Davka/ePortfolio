<!-- HEAD START -->

<head>
  <meta charset="utf-8"/><meta charset="utf-8"/>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
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


    .portfolio-info-wrapper-current .wrapper-name {
      margin-bottom: 10px;
    }

    .portfolio-info-wrapper-current .wrapper-beschreibung {
      margin-bottom: 10px;
    }

    .portfolio-info-trigger {
      font-size: 10px;
      text-align: center;
      border: 1px solid #dddddd;
      padding: 3px 5px;
    }

    .glyphicon-ok {
      color: green;
    }

    .glyphicon-remove {
      color: red;
    }

  </style>
</head>

<!-- HEAD END -->

<!-- <h3>Supervisor</h3>

<?php if(!$supervisorId == NULL):?>

  <?php $supervisor = UserModel::getUser($supervisorId);
      echo $supervisor[Vorname].' '.$supervisor[Nachname].'<br/>';
   ?>

   <div class="avatar-container"><?= Avatar::getAvatar($supervisorId)->getImageTag(Avatar::NORMAL) ?></div>

<?php else: ?>
  <button data-toggle="modal" data-target="#addSupervisorModal" type="button" class="btn btn-default"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Supervisor hinzufuegen</button>
<?php endif;?> -->

<?php if (empty($viewerList))
  echo MessageBox::info('Es sind derzeit keine Zugriffsrechte in Ihrem Portfolio vergeben.');
?>

<table class="default">

  <caption>Zugriffsrechte</caption>
  <tr class="sortable">
    <th>Name</th>
    <?php foreach ($chapterList as $chapter):?>
      <th>
        <?php echo $chapter[title]; ?>
      </th>
    <?php endforeach; ?>
  </tr>

<tbody>

<?php
  //supervisor Zeile
  //Supervisor Informationen
  $supervisorId = SettingsController::getSupervisorOfPortfolio($cid);
  $supervisor = UserModel::getUser($supervisorId);
  $supervisorName = $supervisor[Vorname].' '.$supervisor[Nachname];

  //Freigaben für Portfolio
  $SupervisorFreigaben = SettingsController::getPortfolioFreigaben($cid);
 ?>

<tr style="background-color: lightblue;">
  <td>
    <img style="border-radius: 30px; width: 15px;" src="<?php echo $GLOBALS[DYNAMIC_CONTENT_URL];?>/user/<?php echo $supervisorId; ?>_small.png" onError="defaultImg(this);">
    <?php echo $supervisorName; ?>
  </td>

  <?php foreach ($chapterList as $chapter):?>
    <?php if($SupervisorFreigaben[$chapter[id]] == 1): ?>
      <td id="chapter<?php echo $chapter[id]?>" onclick="freigeben('<?php echo $chapter[id]; ?>', '<?php echo $cid; ?>');"><?php echo  Icon::create('accept', 'clickable'); ?></td>
    <?php else: ?>
      <td id="chapter<?php echo $chapter[id]?>" onclick="freigeben('<?php echo $chapter[id]; ?>', '<?php echo $cid; ?>');"><?php echo  Icon::create('decline', 'clickable'); ?></td>
    <?php endif; ?>
  <?php endforeach; ?>
</tr>

<?php $i = 1; ?>
 <?php foreach ($viewerList as $viewer):?>
   <tr>
     <td>
       <img style="border-radius: 30px; width: 15px;" src="<?php echo $GLOBALS[DYNAMIC_CONTENT_URL];?>/user/<?php echo $viewer[viewer_id]; ?>_small.png" onError="defaultImg(this);">
       <?php echo $viewer[Vorname].' '.$viewer[Nachname]; ?> </td>
     <?php $access = settingsController::getEportfolioAccess($viewer[viewer_id], $cid);?>
     <?php foreach ($chapterList as $chapter):?>

      <?php $viewer_id = $viewer[viewer_id]; ?>
      <td onClick="setAccess(<?php echo $chapter[id]?>, '<?php echo $viewer_id ?>', this); checkIcon('<?php echo $viewer[viewer_id]?>', <?php echo $chapter[id]; ?>);" class="righttable-inner">

        <?php if($access[$chapter[id]] == 1):?>
          <span id="icon-<?php echo $viewer[viewer_id].'-'.$chapter[id]; ?>" class="glyphicon glyphicon-ok" aria-hidden="true"></span>
        <?php elseif($access[$chapter[id]] == 0):?>
          <span id="icon-<?php echo $viewer[viewer_id].'-'.$chapter[id]; ?>" class="glyphicon glyphicon-remove" aria-hidden="true"></span>
        <?php endif;?>

      </td>

      <?php endforeach; ?>

    <?php $i = 1; ?>
   </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?= \Studip\Button::create('Zugriffrechte vergeben', 'klickMichButton', array('data-toggle' => 'modal', 'data-target' => '#addViewerModal', 'id' => "newPortfolio")); ?>

<!-- Modal Suche Supervisor -->
<div class="modal fade" id="addSupervisorModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Supervisor hinzuf�gen</h4>
      </div>
      <div class="modal-body" id="modalDeleteBody">

          <p>
            <div class="input-group" style="margin-bottom:20px;">
              <div class="input-group-addon"><span class="glyphicon glyphicon-search" aria-hidden="true"></span></div>
              <input type="text" class="form-control" id="inputSearchSupervisor" placeholder="Name des Supervisors">
            </div>

            <div id="searchResult"></div>

          </p>


      </div>
    </div>
  </div>
</div>

<!-- Modal Suche Viewer -->
<div class="modal fade" id="addViewerModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Neue Zugriffsrechte vergeben</h4>
      </div>
      <div class="modal-body" id="modalDeleteBody">

          <p>
            <div class="input-group" style="margin-bottom:20px;">
              <div class="input-group-addon"><span class="glyphicon glyphicon-search" aria-hidden="true"></span></div>
              <input type="text" class="form-control" id="inputSearchViewer" placeholder="Name der Person">
            </div>

            <div id="searchResultViewer"></div>

          </p>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript" src="<?php echo $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins_packages/uos/EportfolioPlugin/assets/js/eportfolio.js'; ?>"></script>
<script type="text/javascript">

  var cid = '<?php echo $cid; ?>';

  $( document ).ready(function() {


    $('#deleteModal').on('shown.bs.modal', function () {
      $('#deleteModal').focus()
    })

    // Portfolio Informationen ändern
    $('#portfolio-info-trigger').click( function() {
      $(this).toggleClass('show-info-not');
      $('#portfolio-info-saver').toggleClass('show-info');
      $('.portfolio-info-wrapper').toggleClass('show-info');
      $('.portfolio-info-wrapper-current').toggleClass('show-info-not');
    })

    $('#portfolio-info-saver').click( function() {
      $(this).toggleClass('show-info');
      $('#portfolio-info-trigger').toggleClass('show-info-not');
      $('.portfolio-info-wrapper').toggleClass('show-info');
      $('.portfolio-info-wrapper-current').toggleClass('show-info-not');

      var valName = $("#name-input").val();
      var valBeschreibung = $("#beschreibung-input").val();

      $.ajax({
        type: "POST",
        url: "/studip/plugins.php/eportfolioplugin/settings?cid="+cid,
        data: {'saveChanges': 1, 'Name': valName, 'Beschreibung': valBeschreibung},
        success: function(data) {
          $('.wrapper-name').empty().append('<span>'+valName+'</span>');
          $('.wrapper-beschreibung').empty().append('<span>'+valBeschreibung+'</span>');
        }
      });

    })

    //Search Supervisor
    $('#inputSearchSupervisor').keyup(function() {
      var val = $("#inputSearchSupervisor").val();
      var url = STUDIP.URLHelper.getURL('plugins.php/eportfolioplugin/livesearch');

      $.ajax({
        type: "POST",
        url: url,
        dataType: "json",
        data: {
          'val': val,
          'status': 'dozent',
          'searchSupervisor': 1,
        },
        success: function(json) {
          $('#searchResult').empty();
          _.map(json, output);
          console.log(json);

          function output(n) {
            $('#searchResult').append('<div onClick="setSupervisor(&apos;'+n.userid+'&apos;)" class="searchResultItem">'+n.Vorname+' '+n.Nachname+'<span class="pull-right glyphicon glyphicon-plus" aria-hidden="true"></span></div>');
          }
        }
      });
    });

    //Search Viewer
    $('#inputSearchViewer').keyup(function() {
      var val = $("#inputSearchViewer").val();
      var url = STUDIP.URLHelper.getURL('plugins.php/eportfolioplugin/livesearch');

      var values = _.words(val);

      $.ajax({
        type: "POST",
        url: url,
        dataType: "json",
        data: {
          'val': values,
          'searchViewer': 1,
          'cid': cid,
        },
        success: function(json) {
          $('#searchResultViewer').empty();
            _.map(json, output);
            console.log(json);
            function output(n) {
              console.log(n.userid);
              $('#searchResultViewer').append('<div onClick="setViewer(&apos;'+n.userid+'&apos;)" class="searchResultItem">'+n.Vorname+' '+n.Nachname+'<span class="pull-right glyphicon glyphicon-plus" aria-hidden="true"></span></div>');
            }
        },
        error: function(json){
          console.log(json.responsetext);
          $('#searchResultViewer').empty();
          _.map(json, output);
          function output(n) {
            $('#searchResultViewer').append('<div onClick="setViewer(&apos;'+n.userid+'&apos;)" class="searchResultItem">'+n.Vorname+' '+n.Nachname+'<span class="pull-right glyphicon glyphicon-plus" aria-hidden="true"></span></div>');
          }
        }
      });
    });


  });



</script>
