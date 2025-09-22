<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Products - MJ's Pharmacy</title>
  <link rel="stylesheet" href="{{ asset('css/customer/products.css') }}">
</head>

<body>
  @include('client.client-header')

  <div class="main-wrapper">
    <div class="container">
      <div class="hero-section">
        <div class="search-container">
          <div class="search-wrapper">
            <span class="search-icon">🔍</span>
            <input type="text" class="search-bar" placeholder="Search for medicines, supplements, or health products..." id="searchInput">
          </div>
        </div>
      </div>

      <div class="filters-section">
        <div class="filters-header">
          <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">All Products</button>
            <button class="filter-btn" data-filter="tablet">Tablets</button>
            <button class="filter-btn" data-filter="capsule">Capsules</button>
            <button class="filter-btn" data-filter="syrup">Syrups</button>
            <button class="filter-btn" data-filter="cream">Creams & Ointments</button>
          </div>

          <div class="results-info">
            <span class="results-count" id="resultsCount">
              {{ count($products) }} products found
            </span>
            <div class="view-toggle">
              <button class="view-btn active" data-view="grid" title="Grid View">⚏</button>
              <button class="view-btn" data-view="list" title="List View">☰</button>
            </div>
          </div>
        </div>
      </div>

      <div class="products-grid" id="productsGrid">
        @forelse ($products as $product)
          @php
            $lowestPriceBatch = $product->batches->sortBy('sale_price')->first();
            $highestPriceBatch = $product->batches->sortByDesc('sale_price')->first();
            $showPriceRange = $product->batches->count() > 1 &&
                             $lowestPriceBatch->sale_price != $highestPriceBatch->sale_price;
          @endphp

          <div class="product-card"
               data-category="{{ strtolower($product->form_type) }}"
               data-name="{{ strtolower($product->product_name) }}"
               data-price="{{ $lowestPriceBatch->sale_price }}">

            <div class="product-header">
              <div class="stock-badge stock-available">In Stock</div>
            </div>

            <div class="product-content">
              <h3 class="product-name">{{ $product->product_name }}</h3>
              <p class="product-form">{{ $product->form_type }}</p>

              <p class="product-price">
                @if($showPriceRange)
                  ₱{{ number_format($lowestPriceBatch->sale_price, 2) }} - ₱{{ number_format($highestPriceBatch->sale_price, 2) }}
                @else
                  ₱{{ number_format($lowestPriceBatch->sale_price, 2) }}
                @endif
              </p>

              <div class="product-details">
                @if($product->batches->count() > 1)
                <div class="product-detail product-batches">
                  <span class="product-detail-label">Batches:</span> {{ $product->batches->count() }} available
                </div>
                @endif

                @if(isset($product->generic_name))
                <div class="product-detail">
                  <span class="product-detail-label">Generic:</span> {{ $product->generic_name }}
                </div>
                @endif

                @if(isset($product->dosage))
                <div class="product-detail">
                  <span class="product-detail-label">Dosage:</span> {{ $product->dosage }}
                </div>
                @endif

                @php
                  $earliestBatch = $product->batches->sortBy('expiration_date')->first();
                @endphp
                @if($earliestBatch)
                <div class="product-detail product-expiry">
                  <span class="product-detail-label">Expires:</span> {{ \Carbon\Carbon::parse($earliestBatch->expiration_date)->format('M Y') }}
                </div>
                @endif
              </div>
            </div>
          </div>
        @empty
        <div class="no-products">
          <h3>No available products found</h3>
          <p>Please check back later or contact us for specific medicine inquiries.</p>
        </div>
        @endforelse
      </div>

      @if(isset($products) && method_exists($products, 'links'))
        <div class="pagination-wrapper">
          {{ $products->links() }}
        </div>
      @endif
    </div>
  </div>

  <div class="floating-action">
    <button class="scroll-top" id="scrollTop">↑</button>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('searchInput');
      const productsGrid = document.getElementById('productsGrid');
      const resultsCount = document.getElementById('resultsCount');
      const filterBtns = document.querySelectorAll('.filter-btn');
      const viewBtns = document.querySelectorAll('.view-btn');
      const productCards = document.querySelectorAll('.product-card');
      const scrollTopBtn = document.getElementById('scrollTop');

      let currentFilter = 'all';
      let currentView = 'grid';

      function performSearch() {
        const query = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        productCards.forEach(card => {
          const name = card.dataset.name;
          const category = card.dataset.category;
          const nameElement = card.querySelector('.product-name');
          const originalName = nameElement.textContent;

          nameElement.innerHTML = originalName;

          const matchesSearch = !query || name.includes(query) || category.includes(query);
          const matchesFilter = currentFilter === 'all' || category.includes(currentFilter);

          if (matchesSearch && matchesFilter) {
            card.style.display = 'block';
            visibleCount++;

            if (query && name.includes(query)) {
              const regex = new RegExp(`(${query})`, 'gi');
              nameElement.innerHTML = originalName.replace(regex, '<span class="highlight">$1</span>');
            }
          } else {
            card.style.display = 'none';
          }
        });

        updateResultsCount(visibleCount);
      }

      function applyFilter(filter) {
        currentFilter = filter;

        filterBtns.forEach(btn => {
          btn.classList.toggle('active', btn.dataset.filter === filter);
        });

        performSearch();
      }

      function toggleView(view) {
        currentView = view;

        viewBtns.forEach(btn => {
          btn.classList.toggle('active', btn.dataset.view === view);
        });

        productsGrid.classList.toggle('list-view', view === 'list');
      }

      function updateResultsCount(count) {
        const productText = count === 1 ? 'product' : 'products';
        resultsCount.textContent = `${count} ${productText} found`;
      }

      function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
          const later = () => {
            clearTimeout(timeout);
            func(...args);
          };
          clearTimeout(timeout);
          timeout = setTimeout(later, wait);
        };
      }

      function handleScroll() {
        const scrolled = window.pageYOffset;
        const threshold = 300;

        if (scrolled > threshold) {
          scrollTopBtn.classList.add('visible');
        } else {
          scrollTopBtn.classList.remove('visible');
        }
      }

      searchInput.addEventListener('keyup', debounce(performSearch, 300));

      filterBtns.forEach(btn => {
        btn.addEventListener('click', () => applyFilter(btn.dataset.filter));
      });

      viewBtns.forEach(btn => {
        btn.addEventListener('click', () => toggleView(btn.dataset.view));
      });

      scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });

      window.addEventListener('scroll', handleScroll);

      updateResultsCount(productCards.length);
    });
  </script>
  @stack('scripts')
</body>

</html>
