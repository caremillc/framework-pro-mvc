<?php declare(strict_types=1);

if (!function_exists('dd')) {
    /**
     * Dump and die with styled output and collapsible elements
     * 
     * @param mixed ...$vars Variables to dump
     * @return never
     */
    function dd(...$vars): never
    {
        $isCli = php_sapi_name() === 'cli';
        
        // Start output buffering if in browser mode
        if (!$isCli) {
            // Apply styling for browser output with collapsible functionality
            echo '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Debug Output</title>
                <style>
                    body {
                        font-family: monospace;
                        background-color: #2d2d2d;
                        color: #f8f8f2;
                        padding: 20px;
                        margin: 0;
                    }
                    .dd-container {
                        margin-bottom: 20px;
                        border: 1px solid #444;
                        border-radius: 4px;
                        overflow: hidden;
                    }
                    .dd-header {
                        background-color: #444;
                        color: #f8f8f2;
                        padding: 8px 15px;
                        font-weight: bold;
                        font-size: 14px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }
                    .dd-controls {
                        display: flex;
                        gap: 10px;
                    }
                    .dd-control-btn {
                        cursor: pointer;
                        background-color: #333;
                        color: #f8f8f2;
                        border: none;
                        padding: 4px 8px;
                        border-radius: 4px;
                        font-size: 12px;
                        transition: background-color 0.2s;
                    }
                    .dd-control-btn:hover {
                        background-color: #555;
                    }
                    .dd-content {
                        padding: 15px;
                        overflow: auto;
                        max-height: 500px;
                    }
                    pre {
                        margin: 0;
                        white-space: pre-wrap;
                        font-size: 13px;
                        line-height: 1.5;
                    }
                    .string { color: #a6e22e; }
                    .number { color: #ae81ff; }
                    .boolean { color: #66d9ef; }
                    .null { color: #f92672; }
                    .array { color: #fd971f; }
                    .object { color: #a1efe4; }
                    .property { color: #e6db74; }
                    .backtrace { 
                        margin-top: 15px;
                        padding-top: 15px;
                        border-top: 1px dashed #444;
                        font-size: 12px;
                    }
                    .backtrace-title {
                        color: #f92672;
                        margin-bottom: 8px;
                    }
                    .backtrace-item {
                        padding: 3px 0;
                        color: #75715e;
                    }
                    .backtrace-highlight {
                        color: #e6db74;
                    }
                    .collapsible {
                        cursor: pointer;
                    }
                    .collapse-toggle {
                        display: inline-block;
                        width: 16px;
                        height: 16px;
                        line-height: 16px;
                        text-align: center;
                        background-color: #444;
                        color: #fff;
                        border-radius: 2px;
                        margin-right: 5px;
                        cursor: pointer;
                        font-size: 12px;
                        user-select: none;
                    }
                    .collapsed .collapse-content {
                        display: none;
                    }
                    .collapsed .collapse-toggle:before {
                        content: "+";
                    }
                    .expanded .collapse-toggle:before {
                        content: "-";
                    }
                    .collapse-preview {
                        display: none;
                        color: #75715e;
                        font-style: italic;
                        padding-left: 5px;
                    }
                    .collapsed .collapse-preview {
                        display: inline;
                    }
                    .search-container {
                        margin-bottom: 10px;
                        display: flex;
                        gap: 10px;
                    }
                    .search-input {
                        flex-grow: 1;
                        background-color: #333;
                        color: #f8f8f2;
                        border: 1px solid #444;
                        padding: 8px;
                        border-radius: 4px;
                    }
                    .search-btn {
                        background-color: #444;
                        color: #f8f8f2;
                        border: none;
                        padding: 8px 12px;
                        border-radius: 4px;
                        cursor: pointer;
                    }
                    .search-btn:hover {
                        background-color: #555;
                    }
                    .highlight-search {
                        background-color: #ff8;
                        color: #000;
                    }
                </style>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        // Initialize all collapsible elements
                        initializeCollapsibles();
                        
                        // Add event listeners for expand/collapse all buttons
                        document.querySelectorAll(".expand-all").forEach(btn => {
                            btn.addEventListener("click", function() {
                                const container = this.closest(".dd-container");
                                expandAll(container);
                            });
                        });
                        
                        document.querySelectorAll(".collapse-all").forEach(btn => {
                            btn.addEventListener("click", function() {
                                const container = this.closest(".dd-container");
                                collapseAll(container);
                            });
                        });
                        
                        // Add event listeners for search
                        document.querySelectorAll(".search-form").forEach(form => {
                            form.addEventListener("submit", function(e) {
                                e.preventDefault();
                                const container = this.closest(".dd-container");
                                const searchTerm = this.querySelector(".search-input").value;
                                searchInContainer(container, searchTerm);
                            });
                        });
                    });
                    
                    function initializeCollapsibles() {
                        document.querySelectorAll(".collapse-toggle").forEach(toggle => {
                            toggle.addEventListener("click", function() {
                                const wrapper = this.closest(".collapsible");
                                toggleCollapse(wrapper);
                            });
                        });
                    }
                    
                    function toggleCollapse(element) {
                        if (element.classList.contains("expanded")) {
                            element.classList.remove("expanded");
                            element.classList.add("collapsed");
                        } else {
                            element.classList.remove("collapsed");
                            element.classList.add("expanded");
                        }
                    }
                    
                    function expandAll(container) {
                        container.querySelectorAll(".collapsible").forEach(item => {
                            item.classList.remove("collapsed");
                            item.classList.add("expanded");
                        });
                    }
                    
                    function collapseAll(container) {
                        container.querySelectorAll(".collapsible").forEach(item => {
                            item.classList.remove("expanded");
                            item.classList.add("collapsed");
                        });
                    }
                    
                    function searchInContainer(container, term) {
                        if (!term) return;
                        
                        // Remove previous highlights
                        container.querySelectorAll(".highlight-search").forEach(el => {
                            el.outerHTML = el.innerHTML;
                        });
                        
                        // Simple search (case insensitive)
                        const regex = new RegExp(term, "gi");
                        const content = container.querySelector(".dd-content");
                        
                        // Auto-expand all nodes that contain the search term
                        expandNodesWithTerm(content, term.toLowerCase());
                        
                        // Highlight matches
                        highlightMatches(content, regex);
                    }
                    
                    function expandNodesWithTerm(element, term) {
                        if (element.textContent.toLowerCase().includes(term)) {
                            // Find all parent collapsible elements and expand them
                            let parent = element;
                            while (parent) {
                                if (parent.classList && parent.classList.contains("collapsible")) {
                                    parent.classList.remove("collapsed");
                                    parent.classList.add("expanded");
                                }
                                parent = parent.parentElement;
                            }
                        }
                        
                        // Process children
                        Array.from(element.children).forEach(child => {
                            expandNodesWithTerm(child, term);
                        });
                    }
                    
                    function highlightMatches(element, regex) {
                        // Skip script tags
                        if (element.tagName === "SCRIPT") return;
                        
                        // Check text nodes
                        Array.from(element.childNodes).forEach(node => {
                            if (node.nodeType === 3) { // Text node
                                const content = node.textContent;
                                if (regex.test(content)) {
                                    const highlighted = content.replace(regex, match => 
                                        `<span class="highlight-search">${match}</span>`
                                    );
                                    const tempDiv = document.createElement("div");
                                    tempDiv.innerHTML = highlighted;
                                    
                                    // Replace text node with highlighted content
                                    while (tempDiv.firstChild) {
                                        element.insertBefore(tempDiv.firstChild, node);
                                    }
                                    element.removeChild(node);
                                }
                            } else if (node.nodeType === 1) { // Element node
                                highlightMatches(node, regex);
                            }
                        });
                    }
                </script>
            </head>
            <body>
                <h1 style="color: #f92672; margin-bottom: 20px;">Debug Dump</h1>';
        }

        // Get the backtrace to show where dd() was called from
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $file = $trace[0]['file'] ?? 'unknown';
        $line = $trace[0]['line'] ?? 0;

        // Format and output each variable
        foreach ($vars as $index => $var) {
            $varName = getVariableName($index, $vars, $trace);
            $type = getHumanReadableType($var);
            
            if (!$isCli) {
                echo '<div class="dd-container">';
                echo '<div class="dd-header">';
                echo '<span>' . htmlspecialchars($varName) . ' (' . $type . ')</span>';
                echo '<div class="dd-controls">';
                echo '<button class="dd-control-btn expand-all">Expand All</button>';
                echo '<button class="dd-control-btn collapse-all">Collapse All</button>';
                echo '<span>Called from: ' . htmlspecialchars(basename($file)) . ':' . $line . '</span>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="search-container">';
                echo '<form class="search-form" style="display: flex; width: 100%;">';
                echo '<input type="text" class="search-input" placeholder="Search in this dump..." style="flex-grow: 1;">';
                echo '<button type="submit" class="search-btn">Search</button>';
                echo '</form>';
                echo '</div>';
                
                echo '<div class="dd-content">';
                echo '<pre>';
                
                // Enhanced output with syntax highlighting and collapsible sections
                echo formatVar($var);
                
                echo '</pre>';
                
                // Add backtrace for more context
                echo '<div class="backtrace">';
                echo '<div class="backtrace-title">Stack Trace:</div>';
                
                foreach (array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1, 3) as $i => $trace) {
                    $function = $trace['function'] ?? '';
                    $traceFile = isset($trace['file']) ? basename($trace['file']) : 'unknown';
                    $traceLine = $trace['line'] ?? 0;
                    $class = isset($trace['class']) ? $trace['class'] . $trace['type'] : '';
                    
                    echo '<div class="backtrace-item">';
                    echo '#' . $i . ' ' . $traceFile . '(' . $traceLine . '): ';
                    echo '<span class="backtrace-highlight">' . $class . $function . '()</span>';
                    echo '</div>';
                }
                
                echo '</div>'; // end backtrace
                echo '</div>'; // end dd-content
                echo '</div>'; // end dd-container
            } else {
                // CLI output
                echo "\n\033[1;36m" . $varName . " (" . $type . "):\033[0m\n";
                var_dump(value($var));
                echo "\033[0;90mCalled from: " . $file . ":" . $line . "\033[0m\n";
            }
        }

        if (!$isCli) {
            echo '</body></html>';
        }
        
        exit(1);
    }
}

/**
 * Try to determine the variable name from debug_backtrace
 *
 * @param int $index
 * @param array $vars
 * @param array $trace
 * @return string
 */
function getVariableName(int $index, array $vars, array $trace): string
{
    // Default name if we can't determine actual variable name
    $defaultName = "Variable #" . ($index + 1);
    
    if (!isset($trace[0]['file'])) {
        return $defaultName;
    }
    
    $file = $trace[0]['file'];
    $line = $trace[0]['line'] ?? 0;
    
    if (!file_exists($file)) {
        return $defaultName;
    }
    
    // Get the line where dd() was called
    $fileContent = file($file);
    if (!isset($fileContent[$line - 1])) {
        return $defaultName;
    }
    
    $callingLine = $fileContent[$line - 1];
    
    // Match dd($var) or dd($var, $var2, ...)
    preg_match('/dd\s*\(\s*(.+?)\s*\)/i', $callingLine, $matches);
    
    if (empty($matches[1])) {
        return $defaultName;
    }
    
    // Split the arguments
    $args = explode(',', $matches[1]);
    
    // Clean up the argument
    if (isset($args[$index])) {
        $name = trim($args[$index]);
        // Remove any variable-specific noise
        $name = preg_replace('/\([^)]*\)|\{[^}]*\}|\[[^\]]*\]/', '', $name);
        return $name;
    }
    
    return $defaultName;
}

/**
 * Get a human-readable type for a variable
 *
 * @param mixed $var
 * @return string
 */
function getHumanReadableType(mixed $var): string
{
    if (is_null($var)) {
        return 'null';
    } elseif (is_array($var)) {
        return 'array:' . count($var);
    } elseif (is_object($var)) {
        return 'object:' . get_class($var);
    } elseif (is_bool($var)) {
        return 'boolean:' . ($var ? 'true' : 'false');
    } elseif (is_string($var)) {
        return 'string:' . strlen($var);
    } elseif (is_int($var)) {
        return 'int';
    } elseif (is_float($var)) {
        return 'float';
    } elseif (is_resource($var)) {
        return 'resource:' . get_resource_type($var);
    }
    
    return gettype($var);
}

/**
 * Format a variable with syntax highlighting and collapsible sections
 *
 * @param mixed $var
 * @param int $depth
 * @param int $maxDepth
 * @return string
 */
function formatVar(mixed $var, int $depth = 0, int $maxDepth = 10): string
{
    if ($depth > $maxDepth) {
        return '<span class="null">*MAX DEPTH*</span>';
    }
    
    $output = '';
    
    if (is_null($var)) {
        $output .= '<span class="null">null</span>';
    } elseif (is_bool($var)) {
        $output .= '<span class="boolean">' . ($var ? 'true' : 'false') . '</span>';
    } elseif (is_string($var)) {
        $output .= '<span class="string">"' . htmlspecialchars($var) . '"</span>';
    } elseif (is_int($var) || is_float($var)) {
        $output .= '<span class="number">' . $var . '</span>';
    } elseif (is_array($var)) {
        $count = count($var);
        $isEmpty = $count === 0;
        $collapsibleClass = $depth > 0 ? 'collapsible expanded' : 'collapsible expanded';
        
        $previewContent = '';
        if ($count > 0) {
            $previewItems = array_slice($var, 0, 3, true);
            $previewParts = [];
            foreach ($previewItems as $key => $val) {
                $previewParts[] = is_string($key) ? '"' . $key . '"' : $key;
            }
            if (count($var) > 3) {
                $previewParts[] = '...';
            }
            $previewContent = ' <span class="collapse-preview">' . implode(', ', $previewParts) . '</span>';
        }
        
        $output .= '<span class="' . $collapsibleClass . '">';
        if (!$isEmpty) {
            $output .= '<span class="collapse-toggle"></span>';
        }
        $output .= '<span class="array">array:' . $count . ' [</span>' . $previewContent;
        
        if (!$isEmpty) {
            $output .= '<span class="collapse-content">';
            $indent = str_repeat('  ', $depth + 1);
            $output .= "\n";
            foreach ($var as $key => $value) {
                $output .= $indent . formatKey($key) . ' => ' . formatVar($value, $depth + 1, $maxDepth) . "\n";
            }
            $output .= str_repeat('  ', $depth);
            $output .= '</span>'; // end collapse-content
        }
        
        $output .= '<span class="array">]</span>';
        $output .= '</span>'; // end collapsible
    } elseif (is_object($var)) {
        $className = get_class($var);
        $reflection = new ReflectionObject($var);
        $properties = $reflection->getProperties();
        $isEmpty = count($properties) === 0;
        $collapsibleClass = $depth > 0 ? 'collapsible expanded' : 'collapsible expanded';
        
        $previewContent = '';
        if (!$isEmpty) {
            $previewParts = [];
            $propCount = 0;
            foreach ($properties as $property) {
                if ($propCount >= 3) break;
                $previewParts[] = $property->getName();
                $propCount++;
            }
            if (count($properties) > 3) {
                $previewParts[] = '...';
            }
            $previewContent = ' <span class="collapse-preview">' . implode(', ', $previewParts) . '</span>';
        }
        
        $output .= '<span class="' . $collapsibleClass . '">';
        if (!$isEmpty) {
            $output .= '<span class="collapse-toggle"></span>';
        }
        $output .= '<span class="object">' . $className . ' {</span>' . $previewContent;
        
        if (!$isEmpty) {
            $output .= '<span class="collapse-content">';
            $indent = str_repeat('  ', $depth + 1);
            $output .= "\n";
            
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $propertyName = $property->getName();
                $propertyValue = $property->getValue($var);
                
                $visibility = $property->isPublic() ? 'public' : ($property->isProtected() ? 'protected' : 'private');
                $output .= $indent . '<span class="property">' . $visibility . ' ' . $propertyName . '</span> => ' . formatVar($propertyValue, $depth + 1, $maxDepth) . "\n";
            }
            
            $output .= str_repeat('  ', $depth);
            $output .= '</span>'; // end collapse-content
        }
        
        $output .= '<span class="object">}</span>';
        $output .= '</span>'; // end collapsible
    } elseif (is_resource($var)) {
        $output .= 'resource:' . get_resource_type($var);
    } else {
        $output .= htmlspecialchars(var_export($var, true));
    }
    
    return $output;
}

/**
 * Format an array key with appropriate styling
 *
 * @param mixed $key
 * @return string
 */
function formatKey(mixed $key): string
{
    if (is_string($key)) {
        return '<span class="property">"' . htmlspecialchars($key) . '"</span>';
    } else {
        return '<span class="number">' . $key . '</span>';
    }
}



