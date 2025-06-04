<?php
class filter_echo_url extends moodle_text_filter {
    public function filter($text, array $options = []) {
        if (is_string($text) && strpos($text, '{echo_url}') !== false) {
            return str_replace('{echo_url}', 'HELLO ECHO', $text);
        }
        return $text;
    }
}
