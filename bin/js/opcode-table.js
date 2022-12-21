var HASH_UPDATE_TIMEOUT = 200;
var UNMATCHED_CLASS = "unmatched";
var SEARCH_BAR_PLACEHOLDER = "filter by mnemonic or keywords\u2026 (/)";

function extractKeywords(text) {
  var textNormalized = text.replace(/^\s+|\s+$/g, "").toLowerCase();
  return textNormalized !== "" ? textNormalized.split(/\s+/) : [];
}

function initializeSearch() {
  var unmatchedMatchIndex = -UNMATCHED_CLASS.length - 1;
  var instructionCells = document.getElementsByTagName("td");
  var searchBar = document.createElement("input");
  var searchTimeout = 0;

  function updateHash() {
    window.location.hash = searchBar.value;
  }

  for (
    var instructionCellIndex = 0;
    instructionCellIndex < instructionCells.length;
    instructionCellIndex++
  ) {
    var instructionCell = instructionCells.item(instructionCellIndex);
    var instructionDds = instructionCell.getElementsByTagName("dd");

    if (instructionDds.length !== 0) {
      instructionCell.keywords = extractKeywords(
        instructionCell.getElementsByTagName("code").item(0).textContent
      ).concat(
        extractKeywords(instructionDds.item(0).textContent),
        extractKeywords(
          instructionDds.item(instructionDds.length - 1).textContent
        )
      );
    }
  }

  searchBar.placeholder = SEARCH_BAR_PLACEHOLDER;

  document.body.insertBefore(
    searchBar,
    document.getElementsByTagName("ul").item(0)
  );

  document.onkeydown = function (event) {
    if (event.key === "/") {
      searchBar.focus();
      searchBar.select();
      event.preventDefault();
      event.stopPropagation();
    }
  };

  searchBar.onkeydown = function (event) {
    if (event.key === "Escape") {
      searchBar.blur();
      event.preventDefault();
      event.stopPropagation();
    }
  };

  searchBar.onkeyup = function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(updateHash, HASH_UPDATE_TIMEOUT);
  };

  window.onhashchange = function () {
    var searchQuery = decodeURIComponent(window.location.hash).slice(1);

    if (searchBar.value !== searchQuery) {
      searchBar.value = searchQuery;
    }

    searchKeywords = extractKeywords(searchQuery);

    for (
      var instructionCellIndex = 0;
      instructionCellIndex < instructionCells.length;
      instructionCellIndex++
    ) {
      var instructionCell = instructionCells.item(instructionCellIndex);

      instructionCell.className =
        (instructionCell.className.slice(unmatchedMatchIndex) ===
        " " + UNMATCHED_CLASS
          ? instructionCell.className.slice(0, unmatchedMatchIndex)
          : instructionCell.className) +
        (searchKeywords.length === 0 ||
        ("keywords" in instructionCell &&
          searchKeywords.every(function (searchKeyword) {
            return instructionCell.keywords.some(function (keyword) {
              return keyword.slice(0, searchKeyword.length) === searchKeyword;
            });
          }))
          ? ""
          : " " + UNMATCHED_CLASS);
    }
  };

  window.onhashchange();
}

document.addEventListener("DOMContentLoaded", initializeSearch);
