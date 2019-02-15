<?php

/***************************************************************************
 *
 *	Change Post Author plugin (/inc/plugins/changepostauthor.php)
 *	Author: Julio Hurtado
 *	Copyright: © 2019 Julio Hurtado
 *
 *	Website: https://juliohurtado.xyz
 *
 *	Allows moderators to change post author
 * 
 * This plugin is inpirated in OUGC Admin Post Edit: https://community.mybb.com/mods.php?action=view&pid=647
 * 
 ***************************************************************************

 ****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 ****************************************************************************/

 // Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

// Cache template
if (THIS_SCRIPT == 'editpost.php') {
    global $templatelist;

    if (!isset($templatelist)) {
        $templatelist = '';
    }

    $templatelist .= ',changeauthorpostedit';
}

function changepostauthor_info()
{
    global $lang, $changePostAuthor;

    return $changePostAuthor->_info();


}
function changepostauthor_activate()
{
    global $changePostAuthor;

    return $changePostAuthor->_activate();

}

function changepostauthor_deactivate()
{
    global $changePostAuthor;

    return $changePostAuthor->_deactivate();
}

function changepostauthor_install()
{
}

function changepostauthor_is_installed()
{
    global $changePostAuthor;

    return $changePostAuthor->_is_installed();

}

function changepostauthor_uninstall()
{
    global $changePostAuthor;

    return $changePostAuthor->_uninstall();

}




class ChangePostAuthor
{
    private $update_user = null;
    function __construct()
    {
        global $plugins;

		// Tell MyBB when to run the hook
        if (defined('IN_ADMINCP')) {
            $plugins->add_hook('admin_style_templates_set', array($this, 'load_language'));
        } else {
            $plugins->add_hook('editpost_end', array($this, 'hook_editpost_end'));
            $plugins->add_hook('datahandler_post_update', array($this, 'hook_editpost_end'));
            $plugins->add_hook('editpost_do_editpost_start', array($this, 'hook_editpost_do_editpost_start'));
        }
    }


    function _info()
    {
        global $lang;
        $this->load_language();
        return array(
            'name' => 'Change Post Author',
            'description' => $lang->setting_group_changepostauthor_desc,
            'website' => 'https://github.com/jhurtadojerves/changepostauthor',
            'author' => 'Julio Hurtado',
            'authorsite' => 'https://juliohurtado.xyz',
            'version' => '1.0.0',
            'versioncode' => 100,
            'compatibility' => '18*',
            'codename' => 'changepostauthor',
        );

    }

    function _activate()
    {
        global $PL, $lang, $mybb;
        $this->load_pluginlibrary();
        $PL->templates('changepostauthor', 'Change Post Author', array(
            '' => '
                    <div class="trow2 rowbit">
                        <div class="formbit_label col-sm-2 strong">
                            {$lang->changepostauthor_post}:
                        </div>
                        <div class="formbit_field col-sm-10">
                            <input
                            type="text"
                            class="textbox"
                            name="changepostauthor[username]"
                            id="username"
                            style="width: 16em;"
                            value="{$search_username}"
                            size="28"
                            />
                        </div>
                        <link
                            rel="stylesheet"
                            href="{$mybb->asset_url}/jscripts/select2/select2.css"
                        />
                        <script src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1804"></script>
                        <script>
                            if (use_xmlhttprequest == "1") {
                                MyBB.select2();
                                $("#username").select2({
                                    placeholder: "{$lang->changepostauthor_post_search_user}",
                                    minimumInputLength: 3,
                                    maximumSelectionSize: 3,
                                    multiple: false,
                                    ajax: {
                                    url: "xmlhttp.php?action=get_users",
                                    dataType: "json",
                                    data: function(term, page) {
                                        return {
                                        query: term
                                        };
                                    },
                                    results: function(data, page) {
                                        return { results: data };
                                    }
                                    },
                                    initSelection: function(element, callback) {
                                    var value = $(element).val();
                                    if (value !== "") {
                                        callback({
                                        id: value,
                                        text: value
                                        });
                                    }
                                    },
                                    // Allow the user entered text to be selected as well
                                    createSearchChoice: function(term, data) {
                                    if (
                                        $(data).filter(function() {
                                        return this.text.localeCompare(term) === 0;
                                        }).length === 0
                                    ) {
                                        return { id: term, text: term };
                                    }
                                    }
                                });

                                $("[for=username]").click(function() {
                                    $("#username").select2("open");
                                    return false;
                                });
                            }
                        
                        </script>
                    </div>
                '
        ));
        $PL->settings('changepostauthor', $lang->setting_group_changepostauthor, $lang->setting_group_changepostauthor_desc, array(
            'groups' => array(
                'title' => $lang->setting_group_changepostauthor_groups,
                'description' => $lang->setting_group_changepostauthor_desc,
                'optionscode' => 'groupselect',
                'value' => 4
            ),
        ));

        // Insert/update version into cache
        $plugins = $mybb->cache->read('juliens_plugins');
        if (!$plugins) {
            $plugins = array();
        }

        $this->_info();

        if (!isset($plugins['changepostauthor'])) {
            $plugins['changepostauthor'] = plugin_info['versioncode'];
        }

        $plugins['changepostauthor'] = plugin_info['versioncode'];
        $mybb->cache->update('juliens_plugins', $plugins);

        require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
        find_replace_templatesets('editpost', '#' . preg_quote('{$pollbox}') . '#i', '{$pollbox}{$changepostauthor}');
    }

    function _deactivate()
    {
        require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
        find_replace_templatesets('editpost', '#' . preg_quote('{$changepostauthor}') . '#i', '', 0);
    }
    function _is_installed()
    {
        global $cache;

        $plugins = $cache->read('juliens_plugins');

        return isset($plugins['changepostauthor']);
    }
    function _uninstall()
    {
        global $PL, $cache;
        $this->load_pluginlibrary();

		// Delete settings
        $PL->templates_delete('changepostauthor');
        $PL->settings_delete('changepostauthor');

		// Delete version from cache
        $plugins = (array)$cache->read('juliens_plugins');

        if (isset($plugins['changepostauthor'])) {
            unset($plugins['changepostauthor']);
        }

        if (!empty($plugins)) {
            $cache->update('juliens_plugins', $plugins);
        } else {
            $PL->cache_delete('juliens_plugins');
        }
    }
    function load_language()
    {
        global $lang;

        isset($lang->setting_group_changepostauthor) or $lang->load('changepostauthor');
    }

    function load_pluginlibrary()
    {
        global $lang;
        $this->_info();
        $this->load_language();

        if (!file_exists(PLUGINLIBRARY)) {
            flash_message($lang->sprintf($lang->changepostauthor_pluginlibrary_required, $this->plugin_info['pl']['ulr'], $this->plugin_info['pl']['version']), 'error');
            admin_redirect('index.php?module=config-plugins');
        }

        global $PL;
        $PL or require_once PLUGINLIBRARY;

        if ($PL->version < $this->plugin_info['pl']['version']) {
            global $lang;

            flash_message($lang->sprintf($lang->changepostauthor_pluginlibrary_old, $PL->version, $this->plugin_info['pl']['version'], $this->plugin_info['pl']['ulr']), 'error');
            admin_redirect('index.php?module=config-plugins');
        }
    }

    function hook_editpost_end(&$dh)
    {
        global $fid, $changepostauthor, $mybb;

        $changepostauthor = '';

        if (!is_moderator($fid, 'caneditposts') || !is_member($mybb->settings['changepostauthor_groups'])) {
            return;
        }

        global $lang, $templates, $pid, $db;

        $this->load_language();

        $post = get_post($pid);

        $p = array(
            'uid' => $post['uid'],
            'username' => $post['username'],
        );

        $search_username = '';

        if ($mybb->request_method == 'post') {
            $input = $mybb->get_input('changepostauthor', MyBB::INPUT_ARRAY);

            $post_update_data = array();


            $search_username = htmlspecialchars_uni(trim($input['username']));

            if (!empty($input['username']) && trim($input['username']) && $p['username'] != $input['username']) {
                if ($user = get_user_by_username($input['username'], array('fields' => array('username')))) {
                    $p['uid'] = $post_update_data['uid'] = (int)$user['uid'];
                    $p['username'] = $user['username'];
                    $post_update_data['username'] = $db->escape_string($p['username']);

                    $this->update_user = true;
                }
            }

            if ($dh instanceof PostDataHandler) {
                $dh->post_update_data = array_merge($dh->post_update_data, $post_update_data);

                if (!empty($this->update_user)) {
                    global $plugins;

                    $plugins->add_hook('datahandler_post_update_end', array($this, 'hook_datahandler_post_update_end'));
                }
            }
        }

        $changepostauthor = eval($templates->render('changepostauthor'));
    }

    function hook_datahandler_post_update_end(&$dh)
    {
        global $db;

        $forum = get_forum($dh->data['fid']);

        $thread = get_thread($dh->data['tid']);
        $thread['tid'] = (int)$thread['tid'];

        $query = $db->simple_select('posts', 'pid', "tid='{$thread['tid']}'", array('limit' => 1, 'order_by' => 'dateline', 'order_dir' => 'asc'));
        $firstpost = $db->fetch_field($query, 'pid');

        $post = get_post($dh->data['pid']);

        $new_user = get_user($dh->post_update_data['uid']);
        $new_user['uid'] = (int)$new_user['uid'];

        $thread_update = array();

        if ($this->update_user) {
            $update_query = array();
            if ($forum['usepostcounts']) {
                $update_query['postnum'] = '+1';
            }
            if ($forum['usethreadcounts'] && $firstpost == $post['pid']) {
                $update_query['threadnum'] = '+1';
            }

            if (!empty($update_query)) {
                update_user_counters($new_user['uid'], $update_query);
            }
        }

        if ($firstpost == $post['pid']) {
            $thread_update = array(
                'uid' => $this->update_user ? $dh->post_update_data['uid'] : (int)$post['uid'],
                'username' => $this->update_user ? $dh->post_update_data['username'] : $db->escape_string($post['username'])
            );
        }


        if (!empty($thread_update)) {
            $db->update_query('threads', $thread_update, "tid='{$thread['tid']}'");
        }

        $old_user = get_user($dh->data['uid']);
        $old_user['uid'] = (int)$old_user['uid'];

        if ($this->update_user) {

            $update_query = array();
            if ($forum['usepostcounts']) {
                $update_query['postnum'] = '-1';
            }
            if ($forum['usethreadcounts'] && $firstpost == $post['pid']) {
                $update_query['threadnum'] = '-1';
            }

            if (!empty($update_query)) {
                update_user_counters($old_user['uid'], $update_query);
            }
        }

        update_last_post($dh->data['tid']);
        update_forum_lastpost($dh->data['fid']);
    }
    function hook_editpost_do_editpost_start()
    {
        global $mybb, $fid;

        if (!is_moderator($fid, 'caneditposts') || !is_member($mybb->settings['changepostauthor_groups'])) {
            return;
        }
        $mybb->settings['showeditedbyadmin'] = 0;

    }
}

global $changePostAuthor;

$changePostAuthor = new ChangePostAuthor;