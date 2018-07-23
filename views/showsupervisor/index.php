<? use Studip\LinkButton; ?>


<div class="row">
  <div class="col-md-12">
    <div class="jumbotron" style="border-radius: 10px;">
      <div class="container" style="padding: 0 50px;">

        <h1>Supervisionsgruppe "<?php echo $courseName; ?>"</h1>

      </div>
    </div>
  </div>
</div>

<div>

      <!-- <select class="" id="tempselector" name="template">
        <?php  $templates = Eportfoliomodel::getPortfolioVorlagen(); ?>
        <?php foreach ($templates as $key => $value):?>
          <option value="<?php echo $value[id] ?>"><?php echo $value[temp_name] ?></option>
        <?php endforeach; ?>
      </select>
      <?= \Studip\Button::create('Hinzuf�gen', 'button', array('type' => 'button', 'onclick' => 'addTemp()')); ?> -->

      <div id="wrapper_table_tamplates" style="margin-top: 30px;">
        <h4>Portfoliovorlage hinzuf�gen</h4>

        <table id="table_templates" class="default">
          <colgroup>
            <col width="30%">
            <col width="60%">

          </colgroup>
          <thead>
            <tr class="sortable">
              <th>Portfolio-Name</th>
              <th>Beschreibung</th>
              <th>Aktionen</th>
              <th>Favorit</th>

            </tr>
          </thead>

          <tbody>
            <?php $temps = Eportfoliomodel::getPortfolioVorlagen();
              foreach ($temps as $key):?>
              <?php $thisPortfolio = new Seminar($key); ?>
              <?php $eportfolio = new eportfolio($key); ?>
                <tr>
                  <td><?php echo $thisPortfolio->getName(); ?></td>
                  <td><?php echo $eportfolio->getBeschreibung(); ?></td>
                  <td style="text-align: center;">
                      <a href="<?php echo URLHelper::getLink('plugins.php/courseware/courseware', array('cid' => $key)); ?>"><?php echo Icon::create('edit', 'clickable', ['title' => sprintf(_('Portfolio-Vorlage bearbeiten.'))]) ?></a>
                      <?php if($member && (ShowsupervisorController::checkTemplate($id, $key) == false)): ?>
                      <a onclick="return confirm('Vorlage an Teilnehmende verteilen') " href="<?= PluginEngine::getLink($this->plugin, array(), 'showsupervisor/createportfolio/' . $key . '/' . $id) ?>">
                        <? $params = tooltip2(_("Portfolio-Vorlage an Gruppenmitglieder verteilen.")); ?>
                        <? $params['style'] = 'cursor: pointer'; ?>
                        <?= Icon::create('add', 'clickable')->asImg(20, $params) ?>
                       </a>
                      <?php else: ?>
                        <? $params = tooltip2(_("Vorlage wurde in dieser Gruppe bereits verteilt.")); ?>
                        <?= Icon::create('check-circle', 'clickable')->asImg(20, $params) ?>
                       <?php endif ?>
                  </td>
                  <td style="text-align: center;">

                    <?php if($member && (ShowsupervisorController::checkTemplate($id, $key) == true)): ?>

                      <?php if(EportfolioGroup::checkIfMarkedAsFav($id, $key) == 0): ?>
                        <a href="<?php echo URLHelper::getLink('plugins.php/eportfolioplugin/showsupervisor/addAsFav/'. $id .'/' . $key); ?>">
                          <?= Icon::create('favorite', 'clickable')->asImg(20, $params) ?>
                        </a>
                      <?php else: ?>
                        <a href="<?php echo URLHelper::getLink('plugins.php/eportfolioplugin/showsupervisor/deleteAsFav/'. $id .'/' . $key); ?>">
                          <?= Icon::create('favorite', 'attention')->asImg(20, $params) ?>
                        </a>
                      <?php endif; ?>

                    <?php endif;  ?>

                  </td>
                </tr>

            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

  <?php
    if (empty($groupTemplates[0])):
  ?>



    <h4>Gruppenmitglieder</h4>

    <?php if (!$member):?>
        <?php echo MessageBox::info('Es sind noch keine Nutzer in der der Gruppe eingetragen'); ?>
    <?php else: ?>

      <table class="default">
        <colgroup>
          <col width="30%">
          <col width="60%">
        </colgroup>
        <tr>
          <th>Name</th>
          <th></th>
          <th>Aktionen</th>
        </tr>
        <?php foreach ($member as $user):?>
          <tr>
            <td>
              <?php $userInfo = new User($user);?>
               <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $userInfo['username']) ?>" >
                          <?= Avatar::getAvatar($user, $userInfo['username'])->getImageTag(Avatar::SMALL,
                                array('style' => 'margin-right: 5px; border-radius: 25px; width: 25px; border: 1px solid #28497c;', 'title' => htmlReady($userInfo['Vorname']." ".$userInfo['Nachname']))); ?>
                        <?= htmlReady($userInfo['Vorname']." ".$userInfo['Nachname']) ?>
                   </a>
            </td>
            <td></td>
            <td style="text-align:center;">
                <a href="<?= $controller->url_for(sprintf('showsupervisor/deleteUserFromGroup/%s/%s', $user, $id)) ?>">
                    <?=Icon::create('trash', 'clickable', ['title' => sprintf(_('Nutzer aus Gruppe austragen'))])?>
                </a>
          </tr>
        <?php endforeach; ?>
      </table>

    <?php endif; ?>

    <?php else: ?>

        <div class="grid-container">

          Sortieren nach: Fortschritt

          <div class="row member-container">
            <?php foreach ($member as $user):?>
              <div class="col-sm-4 member-single-card">
                <a class="member-link" data-dialog="size=1000px;" href="/public/plugins.php/eportfolioplugin/showsupervisor/memberdetail">
                <div class="member-item">

                  <div class="member-notification">
                    <?php echo EportfolioGroupUser::getAnzahlAnNeuerungen($member, $id);  ?>
                  </div>

                  <div class="row">
                    <div class="col-sm-4">
                      <div class="member-avatar">
                        <?= Avatar::getAvatar($user, $userInfo['username'])->getImageTag(Avatar::SMALL,array('style' => 'margin-right: 0px; border-radius: 75px; height: 75px; width: 75px; border: 1px solid #28497c;', 'title' => htmlReady($userInfo['Vorname']." ".$userInfo['Nachname']))); ?>
                      </div>
                        <div class="row member-links">
                          <div class="col-sm-4"><?php echo  Icon::create('mail', 'clickable'); ?></div>
                          <div class="col-sm-4"><?php echo  Icon::create('eportfolio', 'clickable'); ?></div>
                          <div class="col-sm-4"><?php echo  Icon::create('accept', 'clickable'); ?></div>
                        </div>
                    </div>
                    <div class="col-sm-8">
                      <div class="member-name">
                        <?php $userInfo = new User($user);?>
                        <?php echo $userInfo['Vorname']; ?> <br>
                        <?php echo $userInfo['Nachname'];?>
                      </div>

                      <div class="member-subname">
                        Status: <span class="member-status-label">.</span> <br>
                        Studiengang etc<br>
                        Letzte Änderung: 12.05 2018
                      </div>
                    </div>
                      <div class="col-sm-12">

                        <?php $favVorlagen = EportfolioGroup::getAllMarkedAsFav($id); ?>
                            <div class="member-content">
                              <div class="row">
                                <?php foreach($favVorlagen as $vorlage): ?>
                                  <?php foreach (Eportfoliomodel::getChapters($vorlage) as $chapter):?>
                                    <?php $new_freigabe = LastVisited::chapter_last_visited($chapter[id], $user) < EportfolioFreigabe::hasAccessSince($supervisorGroupId, $chapter[id]);?>
                                    <div class="col-sm-4 member-kapitelname"><?php echo $chapter[title]?></div>
                                    <div class="col-sm-8">
                                      <div class="row member-icons">
                                        <div class="col-sm-4">
                                          <?php $link = URLHelper::getLink("plugins.php/courseware/courseware", array('cid' => $getsemid , 'selected' => $idNew));?>
                                          <?= $new_freigabe ? Icon::create('accept+new', 'clickable') : Icon::create('accept', 'clickable'); ?>
                                        </div>
                                        <div class="col-sm-4">
                                          <?php if (ShowsupervisorController::checkSupervisorNotiz($idNew) == true): ?>
                                            <?= Icon::create('file', 'clickable'); ?>
                                          <?php else: ?>
                                            <?= Icon::create('file', 'inactive'); ?>
                                          <?php endif; ?>
                                        </div>
                                        <div class="col-sm-4">
                                          <?php if (ShowsupervisorController::checkSupervisorFeedback($idNew) == true): ?>
                                            <?= Icon::create('forum', 'clickable'); ?>
                                          <?php else: ?>
                                            <?= Icon::create('forum', 'inactive'); ?>
                                          <?php endif; ?>
                                        </div>
                                      </div>
                                    </div>
                                  <?php endforeach; ?>
                                <?php  endforeach;?>
                              </div>
                            </div>


                      </div>
                      <div class="col-sm-12">
                        <div class="row member-footer-box">
                          <div class="col-sm-4">
                            <div class="member-footer-box-big">
                              <?php echo EportfolioGroupUser::getAnzahlFreigegebenerKapitel($user, $id); //id soll die gruppenid sein ?>
                              /
                              <?php echo EportfolioGroup::getAnzahlAllerKapitel($id); ?>
                            </div>
                            <div class="member-footer-box-head">
                              freigegeben
                            </div>
                          </div>
                          <div class="col-sm-4">
                            <div class="member-footer-box-big">
                              <?php echo EportfolioGroupUser::getGesamtfortschrittInProzent($member, $id); ?> %
                            </div>
                            <div class="member-footer-box-head">
                              bearbeitet
                            </div>
                          </div>
                          <div class="col-sm-4">
                            <div class="member-footer-box-big">
                              <?php echo EportfolioGroupUser::getAnzahlNotizen($userid, $groupid); ?>
                            </div>
                            <div class="member-footer-box-head">
                              Notizen
                            </div>
                          </div>
                        </div>
                      </div>
                  </div>
                </div>
              </a>
              </div>
            <?php endforeach; ?>
        </div>


            <!-- <div class="grid-item">
              <div class="grid-item-inner">
                <div class="grid-item-inner-head">
                  <div class="grid-item-inner-head-avatar">
                    <?= Avatar::getAvatar($user, $userInfo['username'])->getImageTag(Avatar::SMALL,array('style' => 'margin-right: 5px; border-radius: 25px; width: 25px; border: 1px solid #28497c;', 'title' => htmlReady($userInfo['Vorname']." ".$userInfo['Nachname']))); ?>
                  </div>
                  <div class="grid-item-inner-head-name">
                    <?php echo $userInfo['Vorname']; ?> <br>
                    <?php echo $userInfo['Nachname'];?>
                  </div>
                  <div style="clear: both;"></div>
                </div>
              </div>
            </div> -->

        </div>


            <!-- <button type="button" name="button" onclick="deletetemplate(<?php echo $tempid; ?>)">Vorlage fr diese Gruppe lschen</button> -->
            <!--<?= \Studip\Button::create('Vorlage f�r diese Gruppe l�schen', 'button', array('type' => 'button', 'onclick' => 'deletetemplate('.$tempid.')')); ?>-->
  <?php endif; ?>

</div>


<?php if (empty($groupTemplates)){
     echo $mp;
    }
 ?>


<!-- Legende -->
<div class="legend">
  <ul>
    <li><?php echo  Icon::create('accept', 'clickable'); ?>  Kapitel/Implus freigeschaltet</li>
    <li><?php echo  Icon::create('accept+new', 'clickable'); ?></i>  Kapitel freigeschaltet und �nderungen seit ich das letzte mal reingeschaut habe</li>
    <li><?php echo  Icon::create('file', 'clickable'); ?>  Supervisionsanliegen freigeschaltet</li>
    <li><?php echo  Icon::create('forum', 'clickable'); ?>  Resonanz gegeben</li>
  </ul>
</div>


<script type="text/javascript">

$( function() {
    $( "#vorlagen-tabs" ).tabs();
} );

$('#myModal').on('shown.bs.modal', function () {
$('#myInput').focus()
})

if ( $('#table_templates').find("td").length === 0 ) {
    $('#wrapper_table_tamplates').css('display', 'none');
}


function addUserToGroup(item_id, item_name, item_firstname, item_userid){
  console.log(item_id[0]);
}

function getUserData(id){
  var url = STUDIP.URLHelper.getURL('plugins.php/eportfolioplugin/ajaxsupervisor', {userId: id});
  $.ajax({
    type: "POST",
    url: url,
    dataType: 'JSON',
    success: function(data) {
      $('#userInfoModel').mclassodal('toggle');
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
  var url = STUDIP.URLHelper.getURL('plugins.php/eportfolioplugin/showsupervisor');

  $.ajax({
    type: "POST",
    url: url,
    data: {
      type: 'addTemp',
      groupid: '<?php echo $id ?>',
      tempid: tempid
    },
    success: function(data){
      if (data == "  created") {
        createPortfolio(tempid);
      }
    }
  });
}


function deleteUserFromGroup(userid, obj) {
  var deleteThis    = $(obj).parents('tr');
  var tdParent      = $(obj).parents('td');
  var urlDeleteUser = STUDIP.URLHelper.getURL('plugins.php/eportfolioplugin/showsupervisor');

  $(obj).parents('td').append('<i style="color: #24437c;" class="fa fa-circle-o-notch fa-spin fa-fw"></i>');
  $(obj).remove();


  $.ajax({
    type: "POST",
    url: urlDeleteUser,
    data: {
      action: 'deleteUserFromGroup',
      userId: userid,
      seminar_id: '<?php echo $id ?>',
    },
    success: function(data){
      $(deleteThis).fadeOut();
    }
  });
}

function deletetemplate(tempid){
  var c = confirm("Es werden alle bestehenden ePortfolios dieses Templates gel�scht! M�chten Sie fortfahren?");
  if (c == true){

    var url = STUDIP.URLHelper.getURL('plugins.php/eportfolioplugin/showsupervisor');
    $.ajax({
      type: "POST",
      url: url,
      data: {
        type: 'delete',
        tempid: tempid,
        groupid: '<?php echo $id ?>'
      },
      success: function(data){
        console.log(data);
      }
    });

  } else {
    console.log("cancel");
  }
}


$(document).ready(
  function(){
    $('div[role="tabpanel"]:first').addClass('active');
  }
);

function testfunction(){
  var url = STUDIP.URLHelper.getURL('plugins.php/eportfolioplugin/showsupervisor');
  $.ajax({
    type: "POST",
    url: url,
    data: {
      type: 'addTemplateTest'
    },
    success: function(data){
      console.log(data);
    }
  });
}



var unique = function(origArr) {
    var newArr = [],
        origLen = origArr.length,
        found, x, y;

    for (x = 0; x < origLen; x++) {
        found = undefined;
        for (y = 0; y < newArr.length; y++) {
            if (origArr[x] === newArr[y]) {
                found = true;
                break;
            }
        }
        if (!found) {
            newArr.push(origArr[x]);
        }
    }
    return newArr;
};

var uniqID = function() {
    var ts = +new Date;
    var tsStr = ts.toString();

    var arr = tsStr.split('');
    var rev = arr.reverse();


    var filtered = unique(rev);

    return filtered.join('');

}



</script>
