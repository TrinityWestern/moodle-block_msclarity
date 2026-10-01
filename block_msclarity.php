<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
// without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License along with Moodle.
// If not, see <https://www.gnu.org/licenses/>.

/**
 * Admin-only Microsoft Clarity overview block.
 *
 * @package block_msclarity
 * @copyright 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/** Dashboard display; site-wide hooks and tasks work without an instance. */
class block_msclarity extends block_base {
    /** Initialise the translated title. */
    public function init() {
        $this->title = get_string('pluginname', 'block_msclarity');
    }

    /** @return array Dashboard only, including the default dashboard. */
    public function applicable_formats() {
        return ['all' => false, 'my' => true];
    }

    /** @return bool Has site settings. */
    public function has_config() {
        return true;
    }

    /** @return bool No per-instance settings. */
    public function instance_allow_config() {
        return false;
    }

    /** @return bool One instance per dashboard. */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * @param moodle_page $page Page being edited.
     * @return bool Non-admins cannot add this block, even with explicit capability grants.
     */
    public function user_can_addto($page) {
        return is_siteadmin() && parent::user_can_addto($page);
    }

    /** @return bool Non-admins cannot edit a copied dashboard instance. */
    public function instance_can_be_edited() {
        return is_siteadmin() && parent::instance_can_be_edited();
    }

    /**
     * @param renderer_base $output Output renderer.
     * @return block_contents|null Suppress even the title and editing controls for non-admins.
     */
    public function get_content_for_output($output) {
        if (!\block_msclarity\configuration::can_view()) {
            return null;
        }
        return parent::get_content_for_output($output);
    }

    /**
     * @param renderer_base $output Output renderer.
     * @return stdClass Empty output for non-admin web-service callers too.
     */
    public function get_content_for_external($output) {
        if (!\block_msclarity\configuration::can_view()) {
            return (object)['title' => null, 'content' => null, 'contentformat' => FORMAT_HTML, 'footer' => null, 'files' => []];
        }
        return parent::get_content_for_external($output);
    }

    /** @return stdClass Read the stored snapshot without any HTTP calls. */
    public function get_content() {
        if (!\block_msclarity\configuration::can_view()) {
            return (object)['text' => '', 'footer' => ''];
        }
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = (object)['text' => '', 'footer' => ''];
        $data = ['metrics' => [], 'status' => '', 'stale' => false];
        $projectid = \block_msclarity\configuration::project_id();
        if (!$projectid || !\block_msclarity\configuration::api_token()) {
            $data['status'] = get_string('notconfigured', 'block_msclarity');
        } else {
            $budget = \block_msclarity\request_budget::check($projectid, true, time());
            $data['refreshurl'] = (new moodle_url('/blocks/msclarity/refresh.php'))->out(false);
            $data['sesskey'] = sesskey();
            $data['refreshdisabled'] = $budget['reason'] !== null;
            $data['requestusage'] = get_string('requestusage', 'block_msclarity',
                (object)['used' => $budget['used'], 'limit' => \block_msclarity\request_budget::LIMIT]);
            $data['projectquota'] = get_string('projectquotaunknown', 'block_msclarity');
            if ($budget['nextallowed']) {
                $data['nextrefresh'] = get_string('nextrefresh', 'block_msclarity', userdate($budget['nextallowed']));
            }
            if ($budget['reason'] === 'quota' || $budget['reason'] === 'ratelimit') {
                $data['refreshnotice'] = get_string('refresh_' . $budget['reason'], 'block_msclarity');
            }
            $state = \block_msclarity\insights::state();
            if (!empty($state['updatedat'])) {
                $data['updatedat'] = userdate($state['updatedat']);
                $data['stale'] = !empty($state['error']) || time() >= $state['updatedat'] + \block_msclarity\insights::INTERVAL;
                $values = $state['metrics'] ?? [];
                foreach (['sessions', 'users', 'pagespersession', 'engagement', 'rageclicks', 'deadclicks'] as $key) {
                    $value = $values[$key] ?? null;
                    $precision = in_array($key, ['pagespersession', 'engagement'], true) ? 2 : 0;
                    $item = [
                        'label' => get_string('metric_' . $key, 'block_msclarity'),
                        'value' => $value === null ? get_string('unavailable', 'block_msclarity') : format_float($value, $precision),
                    ];
                    $percentkey = ['rageclicks' => 'ragepercent', 'deadclicks' => 'deadpercent'][$key] ?? null;
                    if ($percentkey && isset($values[$percentkey])) {
                        $item['detail'] = get_string('sessionspercentage', 'block_msclarity', format_float($values[$percentkey], 2));
                    }
                    $data['metrics'][] = $item;
                }
            } else {
                $data['status'] = get_string('awaitingrefresh', 'block_msclarity');
            }
            if (!empty($state['error'])) {
                $data['status'] = get_string('error_' . $state['error'], 'block_msclarity');
            }
        }
        if ($projectid) {
            $data['dashboardurl'] = \block_msclarity\dashboard_url::make()->out(false);
        }
        $this->content->text = $this->page->get_renderer('core')->render_from_template('block_msclarity/overview', $data);
        return $this->content;
    }
}
