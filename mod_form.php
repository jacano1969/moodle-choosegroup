<?php
/* Copyright © 2011 Institut Obert de Catalunya

   This file is part of Choose Group.

   Choose Group is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Choose Group is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/choosegroup/lib.php');

class mod_choosegroup_mod_form extends moodleform_mod {

    function definition() {
        global $COURSE;
        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'choosegroup'),
                           array('size'=>'64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(true, get_string('chatintro', 'chat'));

        $mform->addElement('date_time_selector', 'timeopen', get_string('timeopen', 'choosegroup'), array('optional'=>true));
        $mform->setDefault('timeopen', time());
        $mform->addElement('date_time_selector', 'timeclose', get_string('timeclose', 'choosegroup'), array('optional'=>true));
        $mform->setDefault('timeclose', time()+7*24*3600);

        $options = array('before', 'after', 'closed', 'never');
        foreach ($options as $key=>$option) {
            $options[$key] = get_string("showresults:$option", 'choosegroup');
        }
        $mform->addElement('select', 'showmembers', get_string('showmembers', 'choosegroup'), $options);
        $mform->setDefault('showmembers', count($options)-1);
        $mform->addHelpButton('showmembers', 'showmembers', 'choosegroup');

        $mform->addElement('selectyesno', 'allowupdate', get_string("allowupdate", "choosegroup"));
        $mform->setDefault('allowupdate', 0);
        $mform->addHelpButton('allowupdate', 'allowupdate', 'choosegroup');

        $mform->addElement('selectyesno', 'shownames', get_string("shownames", "choosegroup"));
        $mform->setDefault('shownames', 0);
        $mform->addHelpButton('shownames', 'shownames', 'choosegroup');

        /**********************************************************************************/

        $mform->addElement('header', 'allowgroups', get_string('groups', 'choosegroup'));
        $mform->addHelpButton('allowgroups', 'groups', 'choosegroup');

        $groups = choosegroup_detected_groups($COURSE->id);

        if (empty($groups)) {
            $mform->addElement('static', 'description', get_string('nocoursegroups', 'choosegroup'));
        } else {
            foreach ($groups as $group){
                $buttonarray=array();
                $buttonarray[] =& $mform->createElement('text', 'lgroup['.$group->id.']',get_string('grouplimit','choosegroup'), array('size' => 4));
                $buttonarray[] =& $mform->createElement('checkbox', 'ugroup['.$group->id.']','',' '.get_string('nolimit','choosegroup'));
                $mform->setType('lgroup['.$group->id.']', PARAM_INT);
                $mform->setType('ugroup['.$group->id.']', PARAM_INT);
                $mform->addGroup($buttonarray, 'groupelement', $group->name, array(' '), false);
                $mform->disabledIf('lgroup['.$group->id.']', 'ugroup['.$group->id.']', 'checked');
                $mform->setDefault('lgroup['.$group->id.']', 0);
                $mform->addElement('hidden', 'groupid['.$group->id.']', '');
                $mform->setType('groupid['.$group->id.']', PARAM_INT);
            }
        }

        /**********************************************************************************/

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }


    function data_preprocessing(&$default_values) {
        global $COURSE, $DB;
        $groupsok = $DB->get_records('choosegroup_group', array('choosegroupid' => $this->_instance), 'groupid', 'id, groupid, maxlimit');
        if (!empty($groupsok)) {
            $groups = choosegroup_detected_groups($COURSE->id, true);
            foreach ($groupsok as $group) {
                if (in_array($group->groupid, $groups)){
                    $default_values['ugroup['.$group->groupid.']'] = ($group->maxlimit==0)?1:0;
                    $default_values['lgroup['.$group->groupid.']'] = $group->maxlimit;
                    $default_values['groupid['.$group->groupid.']'] = $group->id;
                }
            }
        }
    }
}
