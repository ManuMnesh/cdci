class SearchEngine {
  constructor() {
    this.searchInput = document.getElementById("searchInput");
    this.searchButton = document.getElementById("searchButton");
    this.resultsList = document.getElementById("resultsList");
    this.resultsContainer = document.getElementById("searchResults");
    this.resultCount = document.getElementById("resultCount");
    this.quickNavButtons = document.getElementById("quickNavButtons");

    this.pages = {
      "index.html": "Home",
      "about.html": "About Us",
      "services.html": "Services",
      "projects.html": "Projects",
      "team.html": "Our Team",
      "news.html": "News",
      "contact.html": "Contact",
    };

    this.setupEventListeners();
    this.setupQuickNav();
  }

  setupEventListeners() {
    this.searchButton.addEventListener("click", () => this.performSearch());
    this.searchInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        this.performSearch();
      }
    });
  }

  setupQuickNav() {
    const currentPage =
      window.location.pathname.split("/").pop() || "index.html";

    Object.entries(this.pages).forEach(([page, title]) => {
      const button = document.createElement("button");
      button.className = "btn btn-outline-primary";
      button.textContent = title;
      if (page === currentPage) {
        button.classList.add("active");
      }
      button.addEventListener("click", () => (window.location.href = page));
      this.quickNavButtons.appendChild(button);
    });
  }

  performSearch() {
    const query = this.searchInput.value.toLowerCase();
    if (!query.trim()) return;

    const results = this.searchContent(query);
    this.displayResults(results);
  }

  searchContent(query) {
    const results = [];
    const searchableElements = document.querySelectorAll(
      "p, h1, h2, h3, h4, h5, h6, .searchable"
    );

    searchableElements.forEach((element) => {
      const text = element.textContent.toLowerCase();
      if (text.includes(query)) {
        const result = {
          title: this.findNearestHeading(element),
          snippet: this.createSnippet(text, query),
          element: element,
        };
        results.push(result);
      }
    });

    return results;
  }

  findNearestHeading(element) {
    let current = element;
    while (current) {
      const headingMatch = current.textContent.match(/^[\s\n]*(.+?)[\s\n]*$/);
      if (headingMatch && current.tagName.match(/^H[1-6]$/)) {
        return headingMatch[1];
      }
      current = current.previousElementSibling || current.parentElement;
    }
    return "Section";
  }

  createSnippet(text, query) {
    const index = text.indexOf(query);
    const start = Math.max(0, index - 50);
    const end = Math.min(text.length, index + query.length + 50);
    let snippet = text.substring(start, end);

    if (start > 0) snippet = "..." + snippet;
    if (end < text.length) snippet = snippet + "...";

    return snippet.replace(
      new RegExp(query, "gi"),
      (match) => `<mark>${match}</mark>`
    );
  }

  displayResults(results) {
    this.resultsContainer.style.display = "block";
    this.resultCount.textContent = results.length;

    if (results.length === 0) {
      this.resultsList.innerHTML =
        "<div class='list-group-item'>No results found</div>";
      return;
    }

    this.resultsList.innerHTML = results
      .map(
        (result) => `
      <div class="list-group-item">
        <h6 class="mb-1">${result.title}</h6>
        <p class="mb-1">${result.snippet}</p>
      </div>
    `
      )
      .join("");

    // Add click handlers
    this.resultsList
      .querySelectorAll(".list-group-item")
      .forEach((item, index) => {
        item.addEventListener("click", () => {
          results[index].element.scrollIntoView({ behavior: "smooth" });
          results[index].element.classList.add("highlight");
          setTimeout(
            () => results[index].element.classList.remove("highlight"),
            2000
          );
          const modal = bootstrap.Modal.getInstance(
            document.getElementById("searchModal")
          );
          modal.hide();
        });
      });
  }
}

// Initialize search when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new SearchEngine();
});
