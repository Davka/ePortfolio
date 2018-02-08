<h1><?php echo $title; ?></h1>

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
  <?php foreach ($usersOfGroup  as $user):?>
    <tr>
      <td>
        <img style="border-radius: 30px; width: 21px; border: 1px solid #28497c;" src="<?php echo $GLOBALS[DYNAMIC_CONTENT_URL];?>/user/<?php echo $user[user_id]; ?>_small.png" onError="defaultImg(this);">
        <?php $userInfo = UserModel::getUser($user[user_id]);?><?php echo $userInfo['Vorname']." ".$userInfo['Nachname']; ?>
      </td>
      <td></td>
      <td style="text-align:center;">
        <a onclick="deleteUserFromGroup('<?php echo $user[user_id]; ?>', this);"><?php echo  Icon::create('trash', 'clickable'); ?></a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php echo $mp; ?>
