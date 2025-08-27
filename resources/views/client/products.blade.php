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
  
  <div class="container">
    <h2 class="page-title">Our Products</h2>  

    <div class="search-section">
      <input type="text" class="search-bar" placeholder="Search for medicines, supplements, or health products..." id="searchInput">
    </div>

    <!-- Enhanced controls section -->
    <div class="controls-section">
      <div class="filter-controls">
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

    <div class="product-grid" id="productGrid">
      @forelse ($products as $product)
        @php
          // Get the batch with the lowest price for display
          $lowestPriceBatch = $product->batches->sortBy('sale_price')->first();
          $highestPriceBatch = $product->batches->sortByDesc('sale_price')->first();
          
          // Determine if we should show a price range or single price
          $showPriceRange = $product->batches->count() > 1 && 
                           $lowestPriceBatch->sale_price != $highestPriceBatch->sale_price;
        @endphp
        
        <div class="product-box" 
             data-category="{{ strtolower($product->form_type) }}" 
             data-name="{{ strtolower($product->product_name) }}"
             data-price="{{ $lowestPriceBatch->sale_price }}">
          
          <!-- Stock status indicator -->
          <div class="stock-status stock-available">
            In Stock
          </div>

          <div class="product-info">
            <p class="product-name">{{ $product->product_name }}</p>
            <p class="product-desc">{{ $product->form_type }}</p>
            
            <!-- Updated price display -->
            <p class="product-price">
              @if($showPriceRange)
                ₱{{ number_format($lowestPriceBatch->sale_price, 2) }} - ₱{{ number_format($highestPriceBatch->sale_price, 2) }}
              @else
                ₱{{ number_format($lowestPriceBatch->sale_price, 2) }}
              @endif
            </p>
            
            <!-- Show available batches count if multiple -->
            @if($product->batches->count() > 1)
            <p class="product-batches">
              <small>{{ $product->batches->count() }} batches available</small>
            </p>
            @endif

            <!-- Additional product details -->
            @if(isset($product->generic_name))
            <p class="product-generic">
              <small>Generic: {{ $product->generic_name }}</small>
            </p>
            @endif

            @if(isset($product->dosage))
            <p class="product-dosage">
              <small>Dosage: {{ $product->dosage }}</small>
            </p>
            @endif
            
            <!-- Show expiration info for the earliest expiring batch -->
            @php
              $earliestBatch = $product->batches->sortBy('expiration_date')->first();
            @endphp
            @if($earliestBatch)
            <p class="product-expiry">
              <small>Expires: {{ \Carbon\Carbon::parse($earliestBatch->expiration_date)->format('M Y') }}</small>
            </p>
            @endif
          </div>
        </div>
      @empty
      <div class="no-products-message">
        <p>No available products found.</p>
        <p><small>Please check back later or contact us for specific medicine inquiries.</small></p>
      </div>
      @endforelse
    </div>

    <!-- Pagination if needed -->
    @if(isset($products) && method_exists($products, 'links'))
      <div class="pagination-wrapper">
        {{ $products->links() }}
      </div>
    @endif
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('searchInput');
      const productGrid = document.getElementById('productGrid');
      const resultsCount = document.getElementById('resultsCount');
      const filterBtns = document.querySelectorAll('.filter-btn');
      const viewBtns = document.querySelectorAll('.view-btn');
      const productBoxes = document.querySelectorAll('.product-box');

      let currentFilter = 'all';
      let currentView = 'grid';

      // Enhanced search functionality with highlighting
      function performSearch() {
        const query = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        productBoxes.forEach(box => {
          const name = box.dataset.name;
          const category = box.dataset.category;
          const nameElement = box.querySelector('.product-name');
          const originalName = nameElement.textContent;
          
          // Reset highlighting
          nameElement.innerHTML = originalName;
          
          const matchesSearch = !query || name.includes(query) || category.includes(query);
          const matchesFilter = currentFilter === 'all' || category.includes(currentFilter);
          
          if (matchesSearch && matchesFilter) {
            box.style.display = 'block';
            box.classList.add('fade-in');
            visibleCount++;
            
            // Highlight matching text
            if (query && name.includes(query)) {
              const regex = new RegExp(`(${query})`, 'gi');
              nameElement.innerHTML = originalName.replace(regex, '<span class="highlight">$1</span>');
            }
          } else {
            box.style.display = 'none';
            box.classList.remove('fade-in');
          }
        });

        updateResultsCount(visibleCount);
      }

      // Filter functionality
      function applyFilter(filter) {
        currentFilter = filter;
        
        // Update active filter button
        filterBtns.forEach(btn => {
          btn.classList.toggle('active', btn.dataset.filter === filter);
        });
        
        performSearch();
      }

      // View toggle functionality
      function toggleView(view) {
        currentView = view;
        
        viewBtns.forEach(btn => {
          btn.classList.toggle('active', btn.dataset.view === view);
        });
        
        productGrid.classList.toggle('list-view', view === 'list');
      }

      // Update results count
      function updateResultsCount(count) {
        const productText = count === 1 ? 'product' : 'products';
        resultsCount.textContent = `${count} ${productText} found`;
      }

      // Event listeners
      searchInput.addEventListener('keyup', debounce(performSearch, 300));
      
      filterBtns.forEach(btn => {
        btn.addEventListener('click', () => applyFilter(btn.dataset.filter));
      });
      
      viewBtns.forEach(btn => {
        btn.addEventListener('click', () => toggleView(btn.dataset.view));
      });

      // Debounce function to limit search frequency
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

      // Sort functionality (can be added)
      function sortProducts(sortBy) {
        const boxes = Array.from(productBoxes);
        
        boxes.sort((a, b) => {
          switch(sortBy) {
            case 'name':
              return a.dataset.name.localeCompare(b.dataset.name);
            case 'price-low':
              return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
            case 'price-high':
              return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
            default:
              return 0;
          }
        });
        
        boxes.forEach(box => productGrid.appendChild(box));
      }

      // Initialize
      updateResultsCount(productBoxes.length);
    });
  </script>
   @stack('scripts')
</body>

</html>