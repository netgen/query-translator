function process(query) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'server.php');
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onload = function() {showData(xhr);};
    xhr.send(JSON.stringify({query: query}));
}

function showData(xhr) {
    if (xhr.status === 200) {
        var data = JSON.parse(xhr.responseText);
        var executionTime = document.getElementById('time');
        var syntaxTree = document.getElementById('syntax-tree');
        var tokenTable = document.getElementById('token-table');
        var corrections = document.getElementById('corrections');
        var correctionCount = document.getElementById('correction-count');
        var translations = document.getElementById('translations');

        executionTime.innerHTML = data.executionTime;
        syntaxTree.innerHTML = data.syntaxTree;
        tokenTable.innerHTML = data.tokenTable;
        corrections.innerHTML = data.corrections;
        correctionCount.innerHTML = data.correctionCount;
        translations.innerHTML = data.translations;
    } else {
        alert('Something is wrong, server returned status ' + xhr.status);
    }
}
