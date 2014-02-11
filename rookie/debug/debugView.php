<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> 
</head>
<body>
<?php
$errorId = uniqid('error');
?>

<style type="text/css">
#rookie_error { background: #ededed; font-size: 1.2em; font-family:sans-serif; text-align: left; color: #111; }
#rookie_error h1,
#rookie_error h2 { margin: 0; padding: 1em; font-size: 1.2em; font-weight: normal; background: #0053b7; color: #fff; }
#rookie_error h1 a,
#rookie_error h2 a { color: #fff; }
#rookie_error h2 { background: #222; }
#rookie_error h3 { margin: 0; padding: 0.4em 0 0; font-size: 1em; font-weight: normal; }
#rookie_error p { margin: 0; padding: 0.2em 0; }
#rookie_error a { color: #1b323b; }
#rookie_error pre { overflow: auto; white-space: pre-wrap; }
#rookie_error table { width: 100%; display: block; margin: 0 0 0.4em; padding: 0; border-collapse: collapse; background: #fff; }
#rookie_error table td { border: solid 1px #ededed; text-align: left; padding: 0.4em; }
#rookie_error div.content { padding: 0.4em 1em 1em; overflow: hidden; }
#rookie_error pre.source { margin: 0 0 1em; padding: 0.4em; background: #fff; border: dotted 1px #b7c680; line-height: 1.4em; }
#rookie_error pre.source span.line { display: block; }
#rookie_error pre.source span.highlight { background: #ffe600; }
#rookie_error pre.source span.line span.number { color: #666; }
#rookie_error ol.trace { display: block; margin: 0 0 0 2em; padding: 0; list-style: decimal; }
#rookie_error ol.trace li { margin: 0; padding: 0; }
.js .collapsed { display: none; }
</style>

<script type="text/javascript">
document.documentElement.className = document.documentElement.className + ' js';
function koggle(elem)
{
    elem = document.getElementById(elem);
    if (elem.style && elem.style['display'])
        var disp = elem.style['display'];
    else if (elem.currentStyle)
        var disp = elem.currentStyle['display'];
    else if (window.getComputedStyle)
        var disp = document.defaultView.getComputedStyle(elem, null).getPropertyValue('display');
    elem.style.display = disp == 'block' ? 'none' : 'block';
    return false;
}
</script>
<div id="rookie_error">
    <h1>
        <span class="type"><?php echo $type ?> [ <?php echo $code ?> ]:</span> 
        <span class="message"><?php echo $message ?></span>
    </h1>
    <div id="<?php echo $errorId ?>" class="content">
        <p>
            <span class="file"><?php echo $file; ?> [ <?php echo $line ?> ]</span>
        </p>
        <?php echo RookieDebug::source($file, $line) ?>
        <ol class="trace">
        <?php foreach (RookieDebug::trace($trace) as $i => $step): ?>
            <li>
                <p>
                    <span class="file">
                        <?php if ($step['file']): $source_id = $errorId.'source'.$i; ?>
                            <a href="#<?php echo $source_id ?>" onclick="return koggle('<?php echo $source_id ?>')"><?php echo $step['file']; ?> [ <?php echo $step['line'] ?> ]</a>
                        <?php else: ?>
                            {<?php echo 'PHP internal call'; ?>}
                        <?php endif ?>
                    </span>
                    &raquo;
                    <?php echo $step['function'] ?>(<?php if ($step['args']): $args_id = $errorId.'args'.$i; ?><a href="#<?php echo $args_id ?>" onclick="return koggle('<?php echo $args_id ?>')"><?php echo 'arguments' ?></a><?php endif ?>)
                </p>
                <?php if (isset($args_id)): ?>
                <div id="<?php echo $args_id ?>" class="collapsed">
                    <table cellspacing="0">
                    <?php foreach ($step['args'] as $name => $arg): ?>
                        <tr>
                            <td><code><?php echo $name ?></code></td>
                            <td><pre><?php echo RookieDebug::dump($arg) ?></pre></td>
                        </tr>
                    <?php endforeach ?>
                    </table>
                </div>
                <?php endif ?>
                <?php if (isset($source_id)): ?>
                    <pre id="<?php echo $source_id ?>" class="source collapsed"><code><?php echo $step['source'] ?></code></pre>
                <?php endif ?>
            </li>
            <?php unset($args_id, $source_id); ?>
        <?php endforeach ?>
        </ol>
    </div>
    <h2><a href="#<?php echo $env_id = $errorId.'environment' ?>" onclick="return koggle('<?php echo $env_id ?>')"><?php echo 'Environment' ?></a></h2>
    <div id="<?php echo $env_id ?>" class="content collapsed">
        <?php $included = get_included_files() ?>
        <h3><a href="#<?php echo $env_id = $errorId.'environment_included' ?>" onclick="return koggle('<?php echo $env_id ?>')"><?php echo 'Included files' ?></a> (<?php echo count($included) ?>)</h3>
        <div id="<?php echo $env_id ?>" class="collapsed">
            <table cellspacing="0">
                <?php foreach ($included as $file): ?>
                <tr>
                    <td><code><?php echo $file ?></code></td>
                </tr>
                <?php endforeach ?>
            </table>
        </div>
        <?php $included = get_loaded_extensions() ?>
        <h3><a href="#<?php echo $env_id = $errorId.'environment_loaded' ?>" onclick="return koggle('<?php echo $env_id ?>')"><?php echo 'Loaded extensions' ?></a> (<?php echo count($included) ?>)</h3>
        <div id="<?php echo $env_id ?>" class="collapsed">
            <table cellspacing="0">
                <?php foreach ($included as $file): ?>
                <tr>
                    <td><code><?php echo $file ?></code></td>
                </tr>
                <?php endforeach ?>
            </table>
        </div>
        <?php foreach (array('_SESSION', '_GET', '_POST', '_FILES', '_COOKIE', '_SERVER') as $var): ?>
        <?php if (empty($GLOBALS[$var]) OR ! is_array($GLOBALS[$var])) continue ?>
        <h3><a href="#<?php echo $env_id = $errorId.'environment'.strtolower($var) ?>" onclick="return koggle('<?php echo $env_id ?>')">$<?php echo $var ?></a></h3>
        <div id="<?php echo $env_id ?>" class="collapsed">
            <table cellspacing="0">
                <?php foreach ($GLOBALS[$var] as $key => $value): ?>
                <tr>
                    <td><code><?php echo $key ?></code></td>
                    <td><pre><?php echo RookieDebug::dump($value) ?></pre></td>
                </tr>
                <?php endforeach ?>
            </table>
        </div>
        <?php endforeach ?>
    </div>
</div>
</body>
</html>