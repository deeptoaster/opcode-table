const AUTOSCROLL_MARGIN = 64;
const AUTOSCROLL_THRESHOLD = 16;
const HASH_UPDATE_TIMEOUT = 200;
const UNMATCHED_CLASS = "unmatched";
const UNMATCHED_MATCH_INDEX = -UNMATCHED_CLASS.length - 1;
const SEARCH_BAR_PLACEHOLDER = "filter by mnemonic or keywords\u2026 (/)";

type InstructionCell = HTMLTableCellElement & {
  keywords: ReadonlyArray<string>;
};

function extractKeywords(query: string): ReadonlyArray<string> {
  const textNormalized = normalizeQuery(query);
  return textNormalized !== "" ? textNormalized.split(/\s+/) : [];
}

function isUnmatched(cell: HTMLTableDataCellElement): boolean {
  return cell.className.slice(UNMATCHED_MATCH_INDEX) === " " + UNMATCHED_CLASS;
}

function initializeSearch(): void {
  const instructionCells = document.getElementsByTagName("td");
  const searchBar = document.createElement("input");
  let searchTimeout = 0;

  function updateHash(): void {
    window.location.hash = searchBar.value !== "" ? searchBar.value : " ";
  }

  for (
    let instructionCellIndex = 0;
    instructionCellIndex < instructionCells.length;
    instructionCellIndex++
  ) {
    const instructionCell = instructionCells.item(
      instructionCellIndex
    ) as InstructionCell;

    const instructionDds = instructionCell.getElementsByTagName("dd");

    if (instructionDds.length !== 0) {
      instructionCell.keywords = extractKeywords(
        instructionCell.getElementsByTagName("code").item(0)?.textContent!
      ).concat(
        extractKeywords(instructionDds.item(0)?.textContent!),
        extractKeywords(
          instructionDds.item(instructionDds.length - 1)?.textContent!
        )
      );
    }
  }

  searchBar.placeholder = SEARCH_BAR_PLACEHOLDER;

  document.body.insertBefore(
    searchBar,
    document.getElementsByTagName("ul").item(0)
  );

  document.onkeydown = function (event: KeyboardEvent): void {
    if (event.key === "/") {
      searchBar.focus();
      searchBar.select();
      event.preventDefault();
      event.stopPropagation();
    }
  };

  searchBar.onkeydown = function (event: KeyboardEvent): void {
    if (event.key === "Enter") {
      if (document.getElementsByClassName(UNMATCHED_CLASS).length !== 0) {
        let scrollOffset = null;
        const autoscrollBuffer =
          searchBar.getBoundingClientRect().height + AUTOSCROLL_MARGIN;

        for (
          let instructionCellIndex = 0;
          instructionCellIndex < instructionCells.length;
          instructionCellIndex++
        ) {
          const instructionCell = instructionCells.item(instructionCellIndex)!;
          const offset = instructionCell.getBoundingClientRect().top;

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

        document.documentElement.scrollTop += scrollOffset! - autoscrollBuffer;
      }
    } else if (event.key === "Escape") {
      searchBar.blur();
      event.preventDefault();
      event.stopPropagation();
    }
  };

  searchBar.onkeyup = function (): void {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(updateHash, HASH_UPDATE_TIMEOUT);
  };

  window.onhashchange = function (): void {
    const searchQuery = decodeURIComponent(window.location.hash).slice(1);

    if (normalizeQuery(searchBar.value) !== normalizeQuery(searchQuery)) {
      searchBar.value = searchQuery;
    }

    const searchKeywords = extractKeywords(searchQuery);

    for (
      let instructionCellIndex = 0;
      instructionCellIndex < instructionCells.length;
      instructionCellIndex++
    ) {
      const instructionCell = instructionCells.item(
        instructionCellIndex
      ) as InstructionCell;

      instructionCell.className =
        (isUnmatched(instructionCell)
          ? instructionCell.className.slice(0, UNMATCHED_MATCH_INDEX)
          : instructionCell.className) +
        (searchKeywords.length === 0 ||
        ("keywords" in instructionCell &&
          searchKeywords.every(function (searchKeyword: string): boolean {
            return instructionCell.keywords.some(function (
              keyword: string
            ): boolean {
              return keyword.slice(0, searchKeyword.length) === searchKeyword;
            });
          }))
          ? ""
          : " " + UNMATCHED_CLASS);
    }
  };

  window.onhashchange(document.createEvent("HashChangeEvent"));
}

function normalizeQuery(query: string): string {
  return query.replace(/^\s+|\s+$/g, "").toLowerCase();
}

document.addEventListener("DOMContentLoaded", initializeSearch);
