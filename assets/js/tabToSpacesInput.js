// From https://stackoverflow.com/a/45396754/446766

var enabled = true;

$('code').keydown(function (e) {
    // Escape key toggles tab on/off
    if (e.keyCode == 27) {
        enabled = !enabled;
        return false;
    }

    // Tab key?
    if (e.keyCode === 9 && enabled) {
        // selection?
        if (this.selectionStart == this.selectionEnd) {
            // These single character operations are undoable
            if (!e.shiftKey) {
                document.execCommand('insertText', false, "  ");
            }
            else {
                var text = $(this).val();
                if (this.selectionStart > 0 && text[this.selectionStart - 1] == '\t') {
                    document.execCommand('delete');
                }
            }
        }
        else {
            // Block indent/unindent trashes undo stack.
            // Select whole lines
            var selStart = this.selectionStart;
            var selEnd = this.selectionEnd;
            var text = $(this).val();
            while (selStart > 0 && text[selStart - 1] != '\n')
                selStart--;
            while (selEnd > 0 && text[selEnd - 1] != '\n' && selEnd < text.length)
                selEnd++;

            // Get selected text
            var lines = text.substr(selStart, selEnd - selStart).split('\n');

            // Insert tabs
            for (var i = 0; i < lines.length; i++) {
                // Don't indent last line if cursor at start of line
                if (i == lines.length - 1 && lines[i].length == 0)
                    continue;

                // Tab or Shift+Tab?
                if (e.shiftKey) {
                    if (lines[i].startsWith('\t'))
                        lines[i] = lines[i].substr(1);
                    else if (lines[i].startsWith("    "))
                        lines[i] = lines[i].substr(4);
                }
                else
                    lines[i] = "\t" + lines[i];
            }
            lines = lines.join('\n');

            // Update the text area
            this.value = text.substr(0, selStart) + lines + text.substr(selEnd);
            this.selectionStart = selStart;
            this.selectionEnd = selStart + lines.length;
        }

        return false;
    }

    enabled = true;
    return true;
});
