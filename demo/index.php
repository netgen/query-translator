<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Query Translator 1.0.x-dev demo</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <script type="text/javascript" src="script.js"></script>
</head>
<body class="syntax-full" onload="process('')">
    <h1>Query Translator 1.0.x-dev demo</h1>
    <p>Switch query syntax: <a class="switch full" onclick="switchSyntax('full');">full</a> <a class="switch text" onclick="switchSyntax('text');">text</a></p>
    <p class="syntax">
        <span>word</span>
        <span>"phrase"</span>
        <span>(group)</span>
        <span>+mandatory</span>
        <span>-prohibited</span>
        <span>AND</span>
        <span>&amp;&amp;</span>
        <span>OR</span>
        <span>||</span>
        <span>NOT</span>
        <span>!</span>
        <span class="syntax-full">#tag</span>
        <span class="syntax-full">@user</span>
        <span class="syntax-full">domain:term</span>
    </p>
    <p>Escape these special characters with a backslash:</p>
    <p class="special">
        <span>(</span>
        <span>)</span>
        <span>+</span>
        <span>-</span>
        <span>!</span>
        <span>"</span>
        <span>\</span>
        <span title="blank space">&blank;</span>
        <span class="syntax-full">#</span>
        <span class="syntax-full">@</span>
        <span class="syntax-full">:</span>
    </p>
    <textarea id="input" placeholder="type your query here" oninput="process(this.value)" autocomplete="off" spellcheck="false" autofocus></textarea>
    <p id="execution">translated in <span id="time">...</span> seconds</p>
    <div class="tabs">
        <form>
            <input class="tab" id="tab1" type="radio" name="tabs" checked>
            <label for="tab1">Syntax tree</label>
            <input class="tab" id="tab2" type="radio" name="tabs">
            <label for="tab2">Tokens</label>
            <input class="tab" id="tab3" type="radio" name="tabs">
            <label for="tab3">Corrections<span id="correction-count"> (0)</span></label>
            <input class="tab" id="tab4" type="radio" name="tabs">
            <label for="tab4">Translations</label>
            <section id="syntax-tree"></section>
            <section id="token-table"></section>
            <section id="corrections"></section>
            <section id="translations"></section>
        </form>
    </div>
</body>
</html>
