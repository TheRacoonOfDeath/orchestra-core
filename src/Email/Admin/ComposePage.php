<?php

declare(strict_types=1);

namespace Orchestra\Core\Email\Admin;

use Orchestra\Core\Email\EmailDraft;
use Orchestra\Core\Email\EmailService;
use Orchestra\Core\Email\RecipientGroup;

/**
 * The "Email" screens: writing an email (with a review step that shows the recipients) and the log.
 *
 * The menu is shown to users who may email at least one group.
 */
final class ComposePage
{
    public const SLUG = 'orchestra-email';
    public const LOG_SLUG = 'orchestra-email-log';

    private const NONCE_ACTION = 'orchestra_core_email_compose';

    /** Recipients listed by name on the review step. */
    private const MAX_LISTED_RECIPIENTS = 200;

    /** @var list<string> */
    private array $errors = [];

    private ?EmailDraft $draft = null;

    public function __construct(private readonly EmailService $email)
    {
    }

    public function register(): void
    {
        \add_action('admin_menu', [$this, 'addMenu']);
    }

    /**
     * @param list<string> $groupIds
     */
    public static function url(array $groupIds = []): string
    {
        $url = \add_query_arg('page', self::SLUG, \admin_url('admin.php'));
        foreach ($groupIds as $id) {
            $url .= '&groups%5B%5D=' . \rawurlencode($id);
        }

        return $url;
    }

    public static function logUrl(?int $sentMessageId = null): string
    {
        $args = ['page' => self::LOG_SLUG];
        if ($sentMessageId !== null) {
            $args['sent'] = $sentMessageId;
        }

        return \add_query_arg($args, \admin_url('admin.php'));
    }

    /**
     * @internal Hooked to `admin_menu`.
     */
    public function addMenu(): void
    {
        if ($this->allowedGroups() === []) {
            return;
        }

        // Access is decided by the groups; `read` only keeps logged-out users away.
        \add_menu_page(
            \__('Email', 'orchestra-core'),
            \__('Email', 'orchestra-core'),
            'read',
            self::SLUG,
            [$this, 'render'],
            'dashicons-email',
            73
        );
        $compose = \add_submenu_page(
            self::SLUG,
            \__('Write Email', 'orchestra-core'),
            \__('Write Email', 'orchestra-core'),
            'read',
            self::SLUG,
            [$this, 'render']
        );
        $log = \add_submenu_page(
            self::SLUG,
            \__('Sent Emails', 'orchestra-core'),
            \__('Sent Emails', 'orchestra-core'),
            'read',
            self::LOG_SLUG,
            [$this, 'renderLog']
        );
        if ($compose !== false) {
            \add_action('load-' . $compose, [$this, 'handleSubmission']);
        }
        if ($log !== false) {
            \add_action('load-' . $log, [$this, 'authorize']);
        }
    }

    /**
     * @internal Hooked to `load-{log page}`.
     */
    public function authorize(): void
    {
        if ($this->allowedGroups() === []) {
            \wp_die(\esc_html__('You are not allowed to send emails.', 'orchestra-core'), 403);
        }
    }

    /**
     * Sends the email before any output, so the browser can be redirected to the log.
     *
     * @internal Hooked to `load-{compose page}`.
     */
    public function handleSubmission(): void
    {
        $this->authorize();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }

        \check_admin_referer(self::NONCE_ACTION);
        $this->draft = EmailDraft::fromForm(\wp_unslash($_POST));
        $operation = $_POST['op'] ?? '';
        if ($operation === 'edit') {
            return;
        }

        $this->errors = $this->email->validate($this->draft, \get_current_user_id());
        if ($operation === 'send' && $this->errors === []) {
            $messageId = $this->email->send($this->draft, \get_current_user_id());
            \wp_safe_redirect(self::logUrl($messageId));
            exit;
        }
    }

    /**
     * @internal Menu page callback.
     */
    public function render(): void
    {
        echo '<div class="wrap"><h1>' . \esc_html__('Write Email', 'orchestra-core') . '</h1>';
        foreach ($this->errors as $error) {
            echo '<div class="notice notice-error"><p>' . \esc_html($error) . '</p></div>';
        }

        $draft = $this->draft;
        if ($draft !== null && $this->errors === [] && ($_POST['op'] ?? '') === 'review') {
            $this->renderReview($draft);
        } else {
            $this->renderForm($draft ?? new EmailDraft(self::requestedGroupIds(), '', ''));
        }
        echo '</div>';
    }

    /**
     * @internal Submenu page callback.
     */
    public function renderLog(): void
    {
        echo '<div class="wrap"><h1 class="wp-heading-inline">' . \esc_html__('Sent Emails', 'orchestra-core') . '</h1>'
            . ' <a class="page-title-action" href="' . \esc_url(self::url()) . '">'
            . \esc_html__('Write Email', 'orchestra-core') . '</a><hr class="wp-header-end">';

        $sentId = \absint(\is_string($_GET['sent'] ?? null) ? $_GET['sent'] : 0);
        $sent = $sentId > 0 ? $this->email->message($sentId) : null;
        if ($sent !== null) {
            $message = $sent->status === 'sent'
                /* translators: %d: number of recipients */
                ? \__('Your email has been sent to %d recipients.', 'orchestra-core')
                /* translators: %d: number of recipients */
                : \__('Your email is being sent to %d recipients. Large groups take a few minutes.', 'orchestra-core');
            echo '<div class="notice notice-success"><p>' . \esc_html(\sprintf($message, $sent->recipients))
                . '</p></div>';
        }

        echo '<p class="description">' . \esc_html(\sprintf(
            /* translators: %d: number of days */
            \__('Texts are deleted once sent. The log shows who emailed which groups for %d days.', 'orchestra-core'),
            $this->email->retentionDays()
        )) . '</p>';

        echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        foreach (
            [
            \__('Date', 'orchestra-core'),
            \__('Sender', 'orchestra-core'),
            \__('Subject', 'orchestra-core'),
            \__('Groups', 'orchestra-core'),
            \__('Recipients', 'orchestra-core'),
            \__('Status', 'orchestra-core'),
            ] as $heading
        ) {
            echo '<th scope="col">' . \esc_html($heading) . '</th>';
        }
        echo '</tr></thead><tbody>';

        $messages = $this->email->log();
        if ($messages === []) {
            echo '<tr><td colspan="6">' . \esc_html__('No emails have been sent yet.', 'orchestra-core') . '</td></tr>';
        }
        $format = self::option('date_format', 'Y-m-d') . ' ' . self::option('time_format', 'H:i');
        foreach ($messages as $message) {
            $sender = $message->senderId === null ? false : \get_userdata($message->senderId);
            $status = $message->status === 'sent'
                ? \__('Sent', 'orchestra-core')
                /* translators: 1: number of sent emails, 2: number of recipients */
                : \sprintf(\__('Sending (%1$d of %2$d)', 'orchestra-core'), $message->sent, $message->recipients);
            if ($message->failed > 0) {
                /* translators: %d: number of emails that could not be sent */
                $status .= ', ' . \sprintf(\__('%d failed', 'orchestra-core'), $message->failed);
            }
            echo '<tr><td>' . \esc_html(\get_date_from_gmt($message->createdAt, $format)) . '</td>'
                . '<td>' . \esc_html($sender === false ? \__('Former member', 'orchestra-core') : $sender->display_name)
                . '</td>'
                . '<td>' . \esc_html($message->subject) . '</td>'
                . '<td>' . \esc_html(\implode(', ', $message->groupLabels)) . '</td>'
                . '<td>' . $message->recipients . '</td>'
                . '<td>' . \esc_html($status) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    private function renderForm(EmailDraft $draft): void
    {
        echo '<form method="post" action="' . \esc_url(self::url()) . '">';
        \wp_nonce_field(self::NONCE_ACTION);
        echo '<input type="hidden" name="op" value="review">';

        echo '<h2>' . \esc_html__('Recipients', 'orchestra-core') . '</h2>';
        $byCategory = [];
        foreach ($this->allowedGroups() as $group) {
            $byCategory[$group->category][] = $group;
        }
        foreach ($byCategory as $category => $groups) {
            $selected = \array_filter(
                $groups,
                static fn (RecipientGroup $g): bool => \in_array($g->id, $draft->groupIds, true)
            );
            $open = $selected !== [] || \count($byCategory) === 1;
            echo '<details style="margin-bottom:.5em"' . ($open ? ' open' : '') . '>'
                . '<summary><strong>' . \esc_html($category) . '</strong>'
                . ($selected !== [] ? ' (' . \count($selected) . ')' : '') . '</summary><p>';
            foreach ($groups as $group) {
                \printf(
                    '<label style="display:block;margin:.25em 0 .25em 1.5em">'
                        . '<input type="checkbox" name="groups[]" value="%s"%s> %s</label>',
                    \esc_attr($group->id),
                    \checked(\in_array($group->id, $draft->groupIds, true), true, false),
                    \esc_html($group->label)
                );
            }
            echo '</p></details>';
        }

        echo '<h2><label for="orchestra-email-subject">' . \esc_html__('Subject', 'orchestra-core') . '</label></h2>'
            . '<input type="text" class="large-text" id="orchestra-email-subject" name="subject" maxlength="'
            . EmailDraft::SUBJECT_MAX_LENGTH . '" value="' . \esc_attr($draft->subject) . '">';
        echo '<h2><label for="orchestra-email-body">' . \esc_html__('Message', 'orchestra-core') . '</label></h2>'
            . '<textarea class="large-text" rows="14" id="orchestra-email-body" name="body">'
            . \esc_textarea($draft->body) . '</textarea>'
            . '<p class="description">' . \esc_html__(
                'Plain text. Every recipient gets their own email; replies go to your email address.',
                'orchestra-core'
            ) . '</p>';
        \submit_button(\__('Review', 'orchestra-core'));
        echo '</form>';
    }

    private function renderReview(EmailDraft $draft): void
    {
        $recipients = $this->email->recipients($draft->groupIds);
        $labels = [];
        foreach ($draft->groupIds as $id) {
            $group = $this->email->groups()->find($id);
            if ($group !== null) {
                $labels[] = EmailService::fullLabel($group);
            }
        }

        $names = [];
        foreach (\array_slice($recipients, 0, self::MAX_LISTED_RECIPIENTS) as $userId) {
            $user = \get_userdata($userId);
            if ($user !== false) {
                $names[] = $user->display_name;
            }
        }
        \natcasesort($names);

        echo '<table class="form-table" role="presentation">'
            . '<tr><th scope="row">' . \esc_html__('Groups', 'orchestra-core') . '</th><td>'
            . \esc_html(\implode(', ', $labels)) . '</td></tr>'
            . '<tr><th scope="row">' . \esc_html__('Recipients', 'orchestra-core') . '</th><td><strong>'
            /* translators: %d: number of recipients */
            . \esc_html(\sprintf(
                \_n('%d person', '%d people', \count($recipients), 'orchestra-core'),
                \count($recipients)
            ))
            . '</strong><br>' . \esc_html(\implode(', ', $names))
            . (\count($recipients) > self::MAX_LISTED_RECIPIENTS ? ', …' : '') . '</td></tr>'
            . '<tr><th scope="row">' . \esc_html__('Subject', 'orchestra-core') . '</th><td>'
            . \esc_html($draft->subject) . '</td></tr>'
            . '<tr><th scope="row">' . \esc_html__('Message', 'orchestra-core') . '</th><td>'
            . '<pre style="white-space:pre-wrap;font:inherit;margin:0">' . \esc_html($draft->body) . '</pre></td></tr>'
            . '</table>';

        $confirm = \sprintf(
            /* translators: %d: number of recipients */
            \__('Send the email to %d people now?', 'orchestra-core'),
            \count($recipients)
        );
        echo '<form method="post" action="' . \esc_url(self::url()) . '">';
        \wp_nonce_field(self::NONCE_ACTION);
        foreach ($draft->groupIds as $id) {
            echo '<input type="hidden" name="groups[]" value="' . \esc_attr($id) . '">';
        }
        echo '<input type="hidden" name="subject" value="' . \esc_attr($draft->subject) . '">'
            . '<textarea name="body" hidden>' . \esc_textarea($draft->body) . '</textarea>'
            . '<p class="submit">'
            . '<button type="submit" name="op" value="send" class="button button-primary" onclick="return confirm('
            . \esc_attr((string) \wp_json_encode($confirm, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT))
            . ');">' . \esc_html__('Send', 'orchestra-core') . '</button> '
            . '<button type="submit" name="op" value="edit" class="button">' . \esc_html__('Edit', 'orchestra-core')
            . '</button></p></form>';
    }

    /**
     * @return list<RecipientGroup>
     */
    private function allowedGroups(): array
    {
        return $this->email->groups()->forUser(\get_current_user_id());
    }

    private static function option(string $name, string $default): string
    {
        $value = \get_option($name);

        return \is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * @return list<string>
     */
    private static function requestedGroupIds(): array
    {
        $groups = $_GET['groups'] ?? [];

        return \is_array($groups)
            ? \array_values(\array_filter(\array_map(
                static fn (mixed $id): string => \is_string($id) ? \sanitize_key($id) : '',
                $groups
            )))
            : [];
    }
}
