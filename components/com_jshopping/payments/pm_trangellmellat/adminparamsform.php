<?php
/**
 * @package     Joomla - > Site and Administrator payment info
 * @subpackage  com_Jshopping
 * @subpackage 	trangell_Mellat
 * @copyright   trangell team => https://trangell.com
 * @copyright   Copyright (C) 20016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die();
?>
<div class="col100">
<fieldset class="adminform">
<table class="admintable" width = "100%" >
 <tr>
   <td  class="key">
     <?php echo 'شماره ترمینال';?>
   </td>
    <td style="text-align: right;">
     <input type = "text" class = "inputbox" name = "pm_params[melatterminalId]" size="100" value = "<?php echo $params['melatterminalId']?>" />
     <?php echo JHTML::tooltip('لطفا شماره ترمینال را وارد کنید');?>
   </td>
 </tr>
 <tr>
   <td  class="key">
     <?php echo  'نام کاربری';?>
   </td>
    <td style="text-align: right;">
     <input type = "text" class = "inputbox" name = "pm_params[melatuser]" size="100" value = "<?php echo $params['melatuser']?>" />
     <?php echo JHTML::tooltip('لطفا نام کاربری را وارد کنید');?>
   </td>
 </tr>
 <tr>
   <td  class="key">
     <?php echo 'کلمه عبور';?>
   </td>
    <td style="text-align: right;">
     <input type = "text" class = "inputbox" name = "pm_params[melatpass]" size="100" value = "<?php echo $params['melatpass']?>" />
     <?php echo JHTML::tooltip('لطفا کلمه عبور را وارد کنید');?>
   </td>
 </tr>
 <tr>
   <td class="key">
     <?php echo _JSHOP_TRANSACTION_END;?>
   </td>
   <td style="text-align: right;">
     <?php              
     print JHTML::_('select.genericlist', $orders->getAllOrderStatus(), 'pm_params[transaction_end_status]', 'class = "inputbox" size = "1"', 'status_id', 'name', $params['transaction_end_status'] );
     ?>
   </td>
 </tr>
 <tr>
   <td class="key">
     <?php echo _JSHOP_TRANSACTION_PENDING;?>
   </td>
   <td style="text-align: right;">
     <?php 
     echo JHTML::_('select.genericlist',$orders->getAllOrderStatus(), 'pm_params[transaction_pending_status]', 'class = "inputbox" size = "1"', 'status_id', 'name', $params['transaction_pending_status']);
     ?>
   </td>
 </tr>
 <tr>
   <td class="key">
     <?php echo _JSHOP_TRANSACTION_FAILED;?>
   </td>
   <td style="text-align: right;">
     <?php 
     echo JHTML::_('select.genericlist',$orders->getAllOrderStatus(), 'pm_params[transaction_failed_status]', 'class = "inputbox" size = "1"', 'status_id', 'name', $params['transaction_failed_status']);
     ?>
   </td>
 </tr>
</table>
</fieldset>
</div>
<div class="clr"></div>
