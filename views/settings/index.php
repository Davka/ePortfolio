<!-- <?php if(!$supervisorId == NULL):?>

  <?php $supervisor = User::find($supervisorId);
      echo $supervisor[Vorname].' '.$supervisor[Nachname].'<br/>';
   ?>

   <div class="avatar-container"><?= Avatar::getAvatar($supervisorId)->getImageTag(Avatar::NORMAL) ?></div>

<?php else: ?>
  <button data-toggle="modal" data-target="#addSupervisorModal" type="button" class="btn btn-default"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Supervisor hinzufuegen</button>
<?php endif;?> -->

<?php if (empty($viewerList) && ($supervisorId == null))
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


<?php if (Eportfoliomodel::findBySeminarId($cid)->group_id): ?>
  <tr style="background-color: lightblue;">
    <td>
    <?= Avatar::getNobody()->getImageTag(Avatar::SMALL,
                                array('style' => 'margin-right: 5px;border-radius: 30px; width: 25px; border: 1px solid #28497c;', 'title' => 'Gruppen-Supervisoren')); ?>
                        Gruppen-Supervisoren         
                    </a>
    </td>

    <?php foreach ($chapterList as $chapter):?>
      <?php $hasAccess = EportfolioFreigabe::hasAccess($supervisorId, $cid, $chapter[id]); ?>
      <td onClick="setAccess('<?= $chapter[id]?>', '<?= $supervisorId ?>', this, '<?= $cid ?>');" class="righttable-inner">

        <?php if($hasAccess):?>
          <span id="icon-<?php echo $supervisorId.'-'.$chapter[id]; ?>" class="glyphicon glyphicon-ok" title='Zugriff sperren'><?= Icon::create('accept', 'clickable'); ?></span>
        <?php else :?>
          <span id="icon-<?php echo $supervisorId.'-'.$chapter[id]; ?>" class="glyphicon glyphicon-remove" title='Zugriff erlauben'><?= Icon::create('decline', 'clickable'); ?></span>
        <?php endif;?>

      </td>

      <?php endforeach; ?>
  </tr>
<?php endif; ?>

<?php $i = 1; ?>
  </tbody>
</table>


<?php echo $mp ?>

<script type="text/javascript" src="<?php echo $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins_packages/uos/EportfolioPlugin/assets/js/eportfolio.js'; ?>"></script>
<script type="text/javascript">

  var cid = '<?php echo $cid; ?>';

  $( document ).ready(function() {


    $('#deleteModal').on('shown.bs.modal', function () {
      $('#deleteModal').focus()
    })

    // Portfolio Informationen ï¿½ndern
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

  function deleteUserAccess(userId, seminar_id, obj){
    $(obj).empty().append('<i style="color: #24437c;" class="fa fa-circle-o-notch fa-spin fa-fw"></i>');
    var url = STUDIP.URLHelper.getURL('plugins.php/eportfolioplugin/settings');
    console.log(userId);
    $.ajax({
      type: "POST",
      url: url,
      data: {
        'action': 'deleteUserAccess',
        'userId': userId,
        'seminar_id': seminar_id,
      },
      success: function(data) {
        console.log(data);
        $(obj).parents('tr').fadeOut();
      }
    });
  }

  function setAccess(id, viewerId, obj, cid){
  var status = $(obj).children('span').hasClass('glyphicon-ok');
  var url = STUDIP.URLHelper.getURL('plugins.php/eportfolioplugin/settings/setAccess/'+viewerId+ '/' +cid+ '/' +id +'/' +!status);
  $.ajax({
    type: "POST",
    url: url,
    success: function(data) {
     if (status === false) {
        $(obj).empty().append('<span class="glyphicon glyphicon-ok"><?php echo  Icon::create('accept', 'clickable'); ?></span>');
      } else {
        $(obj).empty().append('<span class="glyphicon glyphicon-remove"><?php echo  Icon::create('decline', 'clickable'); ?></span>');
      }

    }
  });
}

</script>
