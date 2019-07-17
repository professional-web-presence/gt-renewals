<?php

class gt_renewals_optionspage {
  var $group;
  var $page;
  var $options;

  var $title;
  var $gtNetworkSaveError = '';

  #-- Primary Method --#
  function gt_renewals_optionspage($group, $page, $options) {

    $this -> group = $group;
    $this -> page = $page;
    $this -> options = $options;
    $this -> title = "GT Renewal System Settings";

    if ($_POST && isset($_POST[$group])) {
      $this -> saveNetworkOptions($group);
    }

    if (isset($_REQUEST['gt-renewal-reset']) && ($_REQUEST['gt-renewal-reset'] == 'YES')) {
      global $wpdb;
      $wpdb->get_results("DELETE FROM $wpdb->sitemeta WHERE meta_key LIKE 'gt_%_renewed'");
      $this -> gtNetworkSaveError = "        <div id='message' class='updated fade'><p>All renewal data has been deleted.</p></div>\n";
    }


    $this -> options = get_network_option(1, $group, array());

    add_action('admin_init', array(&$this, 'register_options'));
    add_action('network_admin_menu', array(&$this, 'add_networksettings_page'));

  }



  function saveNetworkOptions($group) {

    #-- Group will normally be gt_renewals_options_ms, but could possibly change in the future --#

    $settings = array();

    foreach (array('enabled','end_date') as $field) {
      $settings[$field] = isset($_POST[$group][$field]) ? $_POST[$group][$field] : '';
    }
    update_network_option(1, $group, $settings);

    $this -> gtNetworkSaveError = "        <div id='message' class='updated fade'><p>Settings saved.</p></div>\n";

  }


  ## Register the options for this plugin so they can be displayed and updated below.
  function register_options() {

    register_setting($this -> group, $this -> group);

    #-- Create the 'gt_renewals_main' section and add fields to it
    $section = 'gt_renewals_main';
    add_settings_section($section, 'General Settings', array(&$this, '_display_options_section'), $this -> page);

    add_settings_field('gt_renewals_enabled', 'Renewals Enabled?', array(&$this, '_display_option_enabled'), $this -> page, $section);

    $fid = $this -> group . '_cas_server';
    add_settings_field('gt_renewals_end_date', '<label for="' . $fid . '">Renewal End Date:</label>', array(&$this, '_display_option_end_date'), $this -> page, $section);

    add_settings_field('gt_renewals_reset_button', 'Reset Renewal Data:', array(&$this, '_display_option_reset_button'), $this -> page, $section);

  }

  #---------- BEGIN OPTIONS DEFINITIONS ----------#

  ## Display explanatory text for the main options section.
  function _display_options_section() {
  }


  ## Display radio buttons
  function _display_radio_buttons($groupID, $value, $radio_options, $noNewlines = 0) {

    print "\n          <p>\n";
    foreach($radio_options as $option => $name) {
      $fid = htmlspecialchars($this -> group) . '_' . htmlspecialchars($groupID) . '_' . $option;
      $fname = htmlspecialchars($this -> group) . '[' . htmlspecialchars($groupID) . ']';

      print '            <input type="radio" name="' . $fname . '" id="' . $fid . '"' . ($value === $option ? ' checked="checked"' : '') . ' value="' . $option . '"/><label for="' . $fid . '">' . $name  . '</label>' . (!$noNewlines ? "<br />\n" : '  ');
    }
    print "           </p>\n";

  }


  ## Display checkbox
  function _display_checkbox($groupID, $value, $label) {

    $fid = htmlspecialchars($this -> group) . '_' . htmlspecialchars($groupID);
    $fname = htmlspecialchars($this -> group) . '[' . htmlspecialchars($groupID) . ']';

    print "\n          <p><input type='checkbox' name='" . $fname . "' id='" . $fid . "'" . ($value ? " checked='checked'" : '') . "/><label for='" . $fid . "'>" . $label  . "</label></p>\n";

  }


  ## Display textfield
  function _display_textfield($groupID, $value, $size, $mode = 0) {

    $fid = htmlspecialchars($this -> group) . '_' . htmlspecialchars($groupID);
    $fname = htmlspecialchars($this -> group) . '[' . htmlspecialchars($groupID) . ']';

    print "\n          <p><input type='" . ($mode == 0 ? 'text' : 'password') . "' name='" . $fname . "' id='" . $fid . "' size='" . $size . "' maxlength='" . $size . "' value='" . htmlspecialchars($value) . "'/></p>\n";

  }

  ## Display the privacy radio buttons
  function _display_option_enabled() {

    $currentValue = $this -> options['enabled'];
    $this -> _display_radio_buttons('enabled', $currentValue, array('N' => 'Renewals are disabled', 'Y' => 'Renewals are enabled'));

    print "          <p class='description'>Disable the system to hide all messages from all users.  When enabling, for a new round of renewals, don't forget to reset the renewal data.</p>\n";

  }

  ## Display the visible end date for renewals Field
  function _display_option_end_date() {

    $currentValue = $this -> options['end_date'];
    $this -> _display_textfield('end_date', $currentValue, 60);

  }


  function _display_option_reset_button() {
        print "  <p><input class='button-primary' type='button' value='Reset Renewal Data' onClick=\"\n";
        print "        if (confirm('Are you sure you want to reset all renewal data?  (You CAN NOT undo this operation!)')) {\n";
        print "          document.location='?page=gt-renewals-config&amp;gt-renewal-reset=YES';\n";
        print "        }\"></p>\n";
  }

  #---------- END OPTIONS DEFINITIONS ----------#


  ## Add a network options page for this plugin.
  function add_networksettings_page() {
    add_submenu_page('settings.php', $this -> title, $this -> title, 'manage_options', $this -> page, array(&$this, '_display_networksettings_page'));
  }


  ## Display the options for this plugin.
  function _display_networksettings_page() {

    $this -> _display_settings_page();

  }


  function _display_settings_page() {

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if ($this -> gtNetworkSaveError != '') {
      print $this -> gtNetworkSaveError;
    }

    print "<div class='wrap'>\n";
    print "          <h1>GT Renewal System Settings</h1>\n";
    print "          <form action='' method='post'>\n";

    #-- Internal WP Fields
    settings_fields($this -> group);
    print "\n";

    #-- The actual options settings that users can see and change
    do_settings_sections($this -> page);
    print "\n";

    print "          <p class='submit'>\n";
    print "            <input type='submit' name='Submit' class='button-primary' value=\"";
    esc_attr_e('Save Changes');
    print "\" />\n";
    print "          </p>\n";
    print "          </form>\n";
    print "        </div>\n";

  }

}

?>
