var AUTOSCROLL_MARGIN = 64;
var AUTOSCROLL_THRESHOLD = 16;
var HASH_UPDATE_TIMEOUT = 200;
var UNMATCHED_CLASS = "unmatched";
var UNMATCHED_MATCH_INDEX = -UNMATCHED_CLASS.length - 1;
var SEARCH_BAR_PLACEHOLDER = "filter by mnemonic or keywords\u2026 (/)";

function extractKeywords(query) {
  var textNormalized = normalizeQuery(query);
  return textNormalized !== "" ? textNormalized.split(/\s+/) : [];
}

function isUnmatched(cell) {
  return cell.className.slice(UNMATCHED_MATCH_INDEX) === " " + UNMATCHED_CLASS;
}

function initializeSearch() {
  var instructionCells = document.getElementsByTagName("td");
  var searchBar = document.createElement("input");
  var searchTimeout = 0;

  function updateHash() {
    window.location.hash = searchBar.value !== "" ? searchBar.value : " ";
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
    if (event.key === "Enter") {
      if (document.getElementsByClassName(UNMATCHED_CLASS).length !== 0) {
        var scrollOffset = null;
        var autoscrollBuffer =
          searchBar.getBoundingClientRect().height + AUTOSCROLL_MARGIN;

        for (
          var instructionCellIndex = 0;
          instructionCellIndex < instructionCells.length;
          instructionCellIndex++
        ) {
          var instructionCell = instructionCells.item(instructionCellIndex);
          var offset = instructionCell.getBoundingClientRect().top;

          if (!isUnmatched(instructionCell)) {
            if (scrollOffset == null) {
              scrollOffset = offset;
            }

            if (offset - autoscrollBuffer >= AUTOSCROLL_THRESHOLD) {
              scrollOffset = offset;
              break;
            }
          }
        }

        document.documentElement.scrollTop += scrollOffset - autoscrollBuffer;
      }
    } else if (event.key === "Escape") {
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

    if (normalizeQuery(searchBar.value) !== normalizeQuery(searchQuery)) {
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
        (isUnmatched(instructionCell)
          ? instructionCell.className.slice(0, UNMATCHED_MATCH_INDEX)
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

function normalizeQuery(query) {
  return query.replace(/^\s+|\s+$/g, "").toLowerCase();
}

document.addEventListener("DOMContentLoaded", initializeSearch);
