/***************************************************************************
 * This file is part of Roundcube "summary" plugin.              
 *                                                                 
 * Your are not allowed to distribute this file or parts of it.    
 *                                                                 
 * This file is distributed in the hope that it will be useful,    
 * but WITHOUT ANY WARRANTY; without even the implied warranty of  
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.          
 *                                                                 
 * Copyright (c) 2012 - 2015 Roland 'Rosali' Liebl - all rights reserved
 * dev-team [at] myroundcube [dot] com
 * http://myroundcube.com
 ***************************************************************************/

Date.prototype.getTimezoneOffsetNoDST = function() {
  var m = 12,
  d = new Date(null, m, 1),
  tzo = d.getTimezoneOffset();

  while (--m) {
    d.setUTCMonth(m);
    if (tzo != d.getTimezoneOffset()) {
        return Math.max(tzo, d.getTimezoneOffset());
    }
  }
  // Probably shouldn't get here.
  return d.getTimezoneOffset();
}

function emptyfolder(myfolder){
  if(rcmail.env.jsdialogconfirm){
    confirm(rcmail.get_label('purgefolderconfirm'), false, "emptyfolder_do('" + myfolder + "')", false);
  }
  else{
    if(confirm(rcmail.get_label('purgefolderconfirm'))){
      emptyfolder_do(myfolder);
    }
  }
}

function emptyfolder_do(myfolder){
  if(rcmail.env.framed){
    document.location.href="./?_action=plugin.summary_purge&_mbox=" + escape(myfolder) + "&_framed=1";
  }
  else{
    document.location.href="./?_action=plugin.summary_purge&_mbox=" + escape(myfolder);
  }
}

function gotofolder(myfolder){
  if(rcmail.env.framed){
    parent.location.href="./?_task=mail&_mbox=" + escape(myfolder);
  }
  else{
    document.location.href="./?_task=mail&_mbox=" + escape(myfolder);
  }
}

function plugin_summary_trigger_refresh(){
  rcmail.http_post('plugin.summary_refresh', '');
  window.setTimeout('plugin_summary_trigger_refresh();', rcmail.env.refresh_interval * 1000);
}

$(document).ready(function(){
  var cltz = new Date();  
  rcmail.addEventListener('plugin.summary_getClientTimezone', plugin_summary_clienttimezone);
  rcmail.addEventListener('plugin.summary_refresh', plugin_summary_refresh);
  rcmail.addEventListener('requestrefresh', plugin_summary_trigger_refresh);
  var dst = 0;
  var realoffset = cltz.getTimezoneOffsetNoDST();
  if(realoffset != cltz.getTimezoneOffset()){
    dst = 1;
  }
  rcmail.http_post('plugin.summary_getClientTimezone', '_cltz=' + realoffset + '&_dst=' + dst);

  function plugin_summary_clienttimezone(response){
    $('#summary_timezone_container').html(response);
  }
  
  function plugin_summary_refresh(response){
    if(response.motd){
      $('#motd').html($(response.motd).first().html());
    }
    if(response.mailbox){
      $('#mailbox').html($(response.mailbox).first().html());
    }
    if(response.quota){
      if($(response.quota).is('fieldset')){
        $('#quota').html($(response.quots).first().html());
      }
    }
  }
});