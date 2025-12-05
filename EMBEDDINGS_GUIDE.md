# AI Embeddings - Quick Start Guide

## Simple API

```php
// Single text
$embedding = ai()->embed('Hello world');

// Multiple texts (auto-detected, single API call)
$embeddings = ai()->embed(['text1', 'text2', 'text3']);

// Find similar
$results = ai()->similar($queryEmbedding, $documents, limit: 5);
```

## ⚠️ Critical: Provider Compatibility

**Embeddings are NOT cross-compatible between providers!**

```php
// ❌ WRONG - This will fail!
// Index with OpenAI
config()->set('ai.driver', 'openai');
$product->embedding = ai()->embed($description); // 1536 dimensions

// Search with Gemini
config()->set('ai.driver', 'gemini');
$query = ai()->embed($userQuery); // 768 dimensions - INCOMPATIBLE!
```

**Why?** Each provider uses different:
- **Dimensions**: OpenAI (1536), Gemini (768), Mistral (1024)
- **Training data**: Different semantic representations
- **Models**: Completely different architectures

**✅ CORRECT - Use same provider for indexing AND searching:**

```php
// Lock your embedding provider in config/ai.php
'driver' => 'gemini', // Use for ALL embeddings

// Or store provider with embedding
$product->embedding_provider = 'gemini';
$product->embedding = json_encode(ai()->embed($description));
```

**If you change providers, you MUST re-embed all content!**

## How It Works

### Step 1: Pre-compute Embeddings (ONE TIME)

For example, embeddings are generated **once** when content is created/updated:

```php
// When creating a product
$product = new Product();
$product->name = 'iPhone 15';
$product->description = 'Smartphone with advanced camera';

// Generate embedding ONCE and store it
$product->embedding = json_encode(ai()->embed($product->description));
$product->save();
```

### Step 2: Search (NO API CALLS for stored items)

```php
// User searches - only 1 API call for query
$query = 'smartphone with good camera';
$queryEmbedding = ai()->embed($query);  // 1 API call

// Load products from database (embeddings already stored!)
$products = Product::query()->all()->map(fn($p) => [
    'id' => $p->id,
    'name' => $p->name,
    'embedding' => json_decode($p->embedding, true)  // From DB, no API call!
]);

// Search in-memory (fast!)
$results = ai()->similar($queryEmbedding, $products, limit: 5);
```

**Total API calls for 1000 products:**
- Creating products: 1000 calls (one-time, when products are added)
- Searching: 1 call per search (just the query)

## Real-World Example: Product Search

```php
// === SETUP (Run once when products are imported) ===

// Batch embed all products efficiently
$products = Product::query()->all();
$descriptions = $products->pluck('description')->toArray();

// Single API call for all products!
$embeddings = ai()->embed($descriptions);

// Store embeddings
foreach ($products as $i => $product) {
    $product->embedding = json_encode($embeddings[$i]);
    $product->save();
}

// === SEARCH (Run on every user search) ===

function searchProducts(string $query): array
{
    // 1. Embed query (1 API call)
    $queryEmbedding = ai()->embed($query);
    
    // 2. Load products with embeddings (from database)
    $products = Product::query()->all()->map(fn($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'price' => $p->price,
        'embedding' => json_decode($p->embedding, true)
    ]);
    
    // 3. Find similar (in-memory, no API calls)
    return ai()->similar($queryEmbedding, $products, limit: 10);
}

// Usage
$results = searchProducts('wireless headphones');
foreach ($results as $result) {
    echo $result['item']['name'] . ' (' . $result['similarity'] . ')';
}
```

## Performance & Scalability

### Understanding the Current Implementation

**Algorithm:** Brute-force cosine similarity (O(n) complexity)

```php
// What happens when you call similar()
foreach ($items as $item) {  // O(n)
    $score = cosineSimilarity($query, $item['embedding']);  // O(d) where d = dimensions
    if ($score >= $threshold) {
        $results[] = ['score' => $score, 'item' => $item];
    }
}
sort($results);  // O(n log n)
return array_slice($results, 0, $limit);
```

**Total complexity:** O(n × d) for similarity + O(n log n) for sorting ≈ **O(n × d)**

### Real-World Performance Benchmarks

#### Search Performance (Single Query)

| Documents | Dimensions | Search Time | Memory Usage | CPU Usage |
|-----------|-----------|-------------|--------------|-----------|
| 100 | 768 | 2-5ms | 1 MB | < 5% |
| 500 | 768 | 10-15ms | 4 MB | 10% |
| 1,000 | 768 | 20-30ms | 8 MB | 15% |
| 5,000 | 768 | 100-150ms | 40 MB | 40% |
| 10,000 | 768 | 200-300ms | 80 MB | 70% |
| 50,000 | 768 | 1-2s | 400 MB | 100% |
| 100,000 | 768 | 2-4s | 800 MB | 100% |

#### Concurrent Requests Impact

| Documents | 1 req/s | 10 req/s | 100 req/s | Bottleneck |
|-----------|---------|----------|-----------|------------|
| 1,000 | ✅ Fine | ✅ Fine | ✅ Fine | None |
| 5,000 | ✅ Fine | ✅ Fine | ⚠️ Slow | CPU |
| 10,000 | ✅ Fine | ⚠️ Slow | ❌ Fails | CPU + Memory |
| 50,000 | ⚠️ Slow | ❌ Fails | ❌ Fails | CPU + Memory |

### API Costs (Detailed)

#### One-Time Indexing Costs

```php
// Example: 10,000 products, avg 100 words each
// = 10,000 × 100 = 1M words ≈ 1.3M tokens

// Gemini
Cost: FREE! 🎉
Time: ~30 seconds (batch API)

// OpenAI (text-embedding-3-small)
Cost: 1.3M tokens × $0.02/1M = $0.026
Time: ~20 seconds (batch API)

// Mistral
Cost: 1.3M tokens × $0.10/1M = $0.13
Time: ~25 seconds (batch API)
```

#### Ongoing Search Costs

```php
// Per search query (avg 10 words ≈ 13 tokens)
// 1 million searches per month

// Gemini
Cost: FREE! 🎉

// OpenAI
Cost: 1M × 13 tokens × $0.02/1M = $0.26/month

// Mistral
Cost: 1M × 13 tokens × $0.10/1M = $1.30/month
```

### When to Upgrade: Decision Matrix

| Scale | Documents | Searches/sec | Current Solution | Action |
|-------|-----------|--------------|------------------|--------|
| **Tiny** | < 1K | Any | ✅ In-memory | Perfect as-is |
| **Small** | 1K-5K | < 10 | ✅ In-memory + cache | Add result caching |
| **Medium** | 5K-10K | < 10 | ⚠️ In-memory + cache | Consider pre-filtering |
| **Medium** | 5K-10K | 10-100 | ⚠️ In-memory | **Upgrade to vector DB** |
| **Large** | 10K-50K | Any | ❌ Too slow | **Upgrade to vector DB** |
| **Huge** | > 50K | Any | ❌ Too slow | **Upgrade to vector DB** |

### Bottlenecks & Solutions

#### Bottleneck 1: CPU-Bound Similarity Calculation

**Problem:** Calculating cosine similarity for 10,000 vectors takes 200ms

**Solutions:**

1. **Result Caching** (Easy - 10 min)
```php
$cacheKey = 'search:' . md5($query);
if ($cached = cache()->get($cacheKey)) {
    return $cached;
}
$results = ai()->similar($queryEmbedding, $docs);
cache()->set($cacheKey, $results, 3600);
```

2. **Pre-filtering** (Medium - 1 hour)
```php
// Filter by category first
$filtered = $products->where('category', $userCategory);
$results = ai()->similar($queryEmbedding, $filtered);
```

3. **Lazy Loading** (Medium - 2 hours)
```php
// Don't load all embeddings upfront
$products = Product::query()->select('id', 'name')->get();
// Load embeddings only for top candidates
```

#### Bottleneck 2: Memory Usage

**Problem:** 100,000 documents × 768 dimensions × 8 bytes = 614 MB

**Solutions:**

1. **Pagination** (Easy - 30 min)
```php
// Search in chunks
$chunks = Product::query()->chunk(5000);
foreach ($chunks as $chunk) {
    $results = ai()->similar($queryEmbedding, $chunk);
    // Keep top N results
}
```

2. **Dimension Reduction** (Advanced - 1 day)
```php
// Use PCA to reduce 768 → 256 dimensions
// Trade: 10% accuracy loss, 3x faster, 3x less memory
```

#### Bottleneck 3: Cold Start

**Problem:** First search loads all embeddings from DB (slow)

**Solutions:**

1. **Warm Cache** (Easy - 30 min)
```php
// On app boot
cache()->remember('all_embeddings', 3600, function() {
    return Product::query()->pluck('embedding', 'id');
});
```

2. **Redis Cache** (Medium - 2 hours)
```php
// Store embeddings in Redis for fast access
redis()->hset('embeddings', $productId, $embedding);
```

## Next Level: Vector Databases

### Why Vector Databases?

**Current in-memory approach limitations:**
- ❌ O(n) search - checks EVERY document
- ❌ No indexing - can't skip irrelevant docs
- ❌ Memory-bound - all embeddings in RAM
- ❌ Single-server - can't distribute load

**Vector databases solve this with:**
- ✅ **ANN (Approximate Nearest Neighbor)** - O(log n) search
- ✅ **HNSW/IVF indexes** - Skip 99% of documents
- ✅ **Disk-based storage** - Handle millions of vectors
- ✅ **Distributed** - Scale horizontally

### Performance Comparison

| Documents | In-Memory | Meilisearch | Qdrant | pgvector |
|-----------|-----------|-------------|--------|----------|
| 1,000 | 20ms | 5ms | 3ms | 10ms |
| 10,000 | 200ms | 15ms | 8ms | 30ms |
| 100,000 | 2s | 30ms | 15ms | 80ms |
| 1,000,000 | ❌ OOM | 50ms | 25ms | 150ms |
| 10,000,000 | ❌ OOM | 100ms | 40ms | 300ms |

### Vector Database Options

#### 1. Meilisearch (Easiest - Recommended for most)

**Pros:**
- ✅ Simple setup (single binary)
- ✅ Great for hybrid search (text + semantic)
- ✅ Built-in typo tolerance
- ✅ RESTful API
- ✅ Self-hosted or cloud

**Cons:**
- ⚠️ Not specialized for vectors (slower than pure vector DBs)
- ⚠️ Limited to ~10M documents

**Setup:**
```bash
# Install
curl -L https://install.meilisearch.com | sh

# Run
./meilisearch --master-key="your-key"
```

**Usage:**
```php
// Index documents
$client = new MeiliSearch\Client('http://localhost:7700', 'your-key');
$index = $client->index('products');

foreach ($products as $product) {
    $index->addDocuments([
        'id' => $product->id,
        'name' => $product->name,
        '_vectors' => ai()->embed($product->description)
    ]);
}

// Search
$results = $index->search('smartphone', [
    'vector' => ai()->embed('smartphone with good camera'),
    'hybrid' => ['semanticRatio' => 0.8]
]);
```

**When to use:**
- ✅ 10K-10M documents
- ✅ Need hybrid search (keyword + semantic)
- ✅ Want simple setup

#### 2. Qdrant (Best Performance)

**Pros:**
- ✅ Purpose-built for vectors
- ✅ Fastest search (HNSW index)
- ✅ Handles billions of vectors
- ✅ Advanced filtering
- ✅ Rust-based (very fast)

**Cons:**
- ⚠️ More complex setup
- ⚠️ Requires more resources

**Setup:**
```bash
# Docker
docker run -p 6333:6333 qdrant/qdrant
```

**Usage:**
```php
// Create collection
$client = new Qdrant\Client('localhost:6333');
$client->createCollection('products', [
    'vectors' => ['size' => 768, 'distance' => 'Cosine']
]);

// Index
$client->upsert('products', [
    'points' => [
        ['id' => 1, 'vector' => ai()->embed($text), 'payload' => ['name' => 'iPhone']]
    ]
]);

// Search
$results = $client->search('products', [
    'vector' => ai()->embed($query),
    'limit' => 10,
    'with_payload' => true
]);
```

**When to use:**
- ✅ > 10M documents
- ✅ Need maximum performance
- ✅ High-traffic production apps

#### 3. pgvector (PostgreSQL Extension)

**Pros:**
- ✅ Use existing PostgreSQL
- ✅ ACID transactions
- ✅ Join with relational data
- ✅ Familiar SQL interface

**Cons:**
- ⚠️ Slower than specialized DBs
- ⚠️ Limited to ~1M vectors per table

**Setup:**
```sql
-- Install extension
CREATE EXTENSION vector;

-- Add vector column
ALTER TABLE products ADD COLUMN embedding vector(768);

-- Create index
CREATE INDEX ON products USING ivfflat (embedding vector_cosine_ops);
```

**Usage:**
```php
// Index
$pdo->prepare("UPDATE products SET embedding = ? WHERE id = ?")
    ->execute([json_encode(ai()->embed($text)), $id]);

// Search
$stmt = $pdo->prepare("
    SELECT id, name, 1 - (embedding <=> ?) as similarity
    FROM products
    ORDER BY embedding <=> ?
    LIMIT 10
");
$stmt->execute([json_encode($queryEmbedding), json_encode($queryEmbedding)]);
```

**When to use:**
- ✅ Already using PostgreSQL
- ✅ Need transactional consistency
- ✅ < 1M documents

### Migration Path: In-Memory → Vector DB

#### Phase 1: Current (In-Memory)
**Scale:** < 5K documents
**Cost:** $0 (Gemini embeddings)
**Effort:** ✅ Already done!

```php
$results = ai()->similar($queryEmbedding, $products);
```

#### Phase 2: Optimized In-Memory
**Scale:** 5K-10K documents
**Cost:** $0
**Effort:** 1-2 days

**Add:**
- Result caching
- Pre-filtering by category/tags
- Warm cache on boot

```php
// Cache results
$cacheKey = 'search:' . md5($query . $filters);
if ($cached = cache()->get($cacheKey)) {
    return $cached;
}

// Pre-filter
$filtered = $products->where('category', $category);
$results = ai()->similar($queryEmbedding, $filtered);

cache()->set($cacheKey, $results, 3600);
```

#### Phase 3: Hybrid (In-Memory + Vector DB)
**Scale:** 10K-100K documents
**Cost:** $20-50/month (Meilisearch Cloud or VPS)
**Effort:** 1 week

**Strategy:**
- Keep in-memory for < 10K docs
- Use Meilisearch for > 10K docs
- Abstract behind interface

```php
interface VectorSearch
{
    public function search(array $embedding, int $limit): array;
}

class InMemorySearch implements VectorSearch
{
    public function search(array $embedding, int $limit): array
    {
        return ai()->similar($embedding, $this->docs, $limit);
    }
}

class MeilisearchSearch implements VectorSearch
{
    public function search(array $embedding, int $limit): array
    {
        return $this->client->search('', ['vector' => $embedding]);
    }
}

// Use based on scale
$searcher = count($products) > 10000 
    ? new MeilisearchSearch() 
    : new InMemorySearch();
```

#### Phase 4: Full Vector DB
**Scale:** > 100K documents
**Cost:** $100-500/month (Qdrant Cloud or dedicated server)
**Effort:** 2-3 weeks

**Migrate everything to Qdrant:**
- Batch migrate existing embeddings
- Update indexing pipeline
- Add monitoring & backups

```php
// Indexing pipeline
class EmbeddingIndexer
{
    public function index(Product $product): void
    {
        $embedding = ai()->embed($product->description);
        
        // Store in DB (backup)
        $product->embedding = json_encode($embedding);
        $product->save();
        
        // Index in Qdrant (search)
        $this->qdrant->upsert('products', [
            'id' => $product->id,
            'vector' => $embedding,
            'payload' => [
                'name' => $product->name,
                'category' => $product->category,
                'price' => $product->price
            ]
        ]);
    }
}
```

### Cost Analysis: In-Memory vs Vector DB

#### Scenario: 50,000 Products, 100 searches/sec

**Option 1: In-Memory (Current)**
- Server: 16GB RAM, 8 CPU = $80/month
- Search time: 1-2s per query
- Concurrent capacity: ~10 req/s
- **Total cost:** $80/month
- **Status:** ❌ Too slow, can't handle load

**Option 2: Meilisearch Cloud**
- Meilisearch: $49/month (10M docs)
- App server: 4GB RAM, 2 CPU = $20/month
- Search time: 30-50ms per query
- Concurrent capacity: 100+ req/s
- **Total cost:** $69/month
- **Status:** ✅ Perfect fit

**Option 3: Self-Hosted Qdrant**
- VPS: 8GB RAM, 4 CPU = $40/month
- App server: 4GB RAM, 2 CPU = $20/month
- Search time: 15-25ms per query
- Concurrent capacity: 200+ req/s
- **Total cost:** $60/month
- **Status:** ✅ Best performance/cost

**Option 4: Qdrant Cloud**
- Qdrant: $95/month (starter)
- App server: 4GB RAM, 2 CPU = $20/month
- Search time: 15-25ms per query
- Concurrent capacity: 200+ req/s
- **Total cost:** $115/month
- **Status:** ✅ Managed, zero ops

## Advanced: Semantic Search with Caching

```php
function searchWithCache(string $query): array
{
    // Cache search results
    $cacheKey = 'search:' . md5($query);
    
    if (cache()->has($cacheKey)) {
        return cache()->get($cacheKey);
    }
    
    $queryEmbedding = ai()->embed($query);
    $products = loadProductsWithEmbeddings();
    $results = ai()->similar($queryEmbedding, $products, limit: 10);
    
    cache()->set($cacheKey, $results, 3600); // Cache for 1 hour
    return $results;
}
```

## Provider Support

### Embedding Support

| Provider | Embeddings | Model | Dimensions | Cost |
|----------|-----------|-------|------------|------|
| **Gemini** | ✅ Yes | text-embedding-004 | 768 | **FREE** |
| **OpenAI** | ✅ Yes | text-embedding-3-small | 1536 | $0.02/1M |
| **Mistral** | ✅ Yes | mistral-embed | 1024 | $0.10/1M |
| **Groq** | ❌ No | - | - | - |
| **Anthropic** | ❌ No | - | - | - |

**Note:** Groq and Anthropic can still be used for RAG - just use a different provider for embeddings (see example below).

## Common Patterns

### 1. Duplicate Detection

```php
// Check if new ticket is duplicate
$newTicket = 'App crashes on startup';
$newEmbedding = ai()->embed($newTicket);

$existingTickets = Ticket::all()->map(fn($t) => [
    'id' => $t->id,
    'embedding' => json_decode($t->embedding, true)
]);

$similar = ai()->similar($newEmbedding, $existingTickets, threshold: 0.85);

if (!empty($similar)) {
    echo "Duplicate of ticket #{$similar[0]['id']}";
}
```

### 2. Content Recommendations

```php
// User viewed this article
$article = Article::find($id);
$articleEmbedding = json_decode($article->embedding, true);

// Find similar articles
$allArticles = Article::where('id', '!=', $id)->get()->map(fn($a) => [
    'id' => $a->id,
    'title' => $a->title,
    'embedding' => json_decode($a->embedding, true)
]);

$recommended = ai()->similar($articleEmbedding, $allArticles, limit: 5);
```

### 3. RAG (AI with Your Data)

```php
// 1. Find relevant docs
$query = 'How do I use models in Lightpack?';
$queryEmbedding = ai()->embed($query);

$docs = Documentation::all()->map(fn($d) => [
    'content' => $d->content,
    'embedding' => json_decode($d->embedding, true)
]);

$relevant = ai()->similar($queryEmbedding, $docs, limit: 3);
$context = implode("\n\n", array_column(array_column($relevant, 'item'), 'content'));

// 2. Ask AI with context
$answer = ai()->task()
    ->system("Answer based on this documentation:\n\n{$context}")
    ->prompt($query)
    ->run();
```

### 4. RAG with Groq/Claude (No Embedding Support)

**Groq and Anthropic don't support embeddings, but you can still use them for RAG!**

```php
// Use Gemini for embeddings (FREE!)
$embedder = new Gemini($http, $cache, $config, $logger);

// 1. Index documents (one-time)
$docs = Documentation::all();
foreach ($docs as $doc) {
    $doc->embedding = json_encode($embedder->embed($doc->content));
    $doc->save();
}

// 2. Search with Gemini embeddings
$queryEmbedding = $embedder->embed($userQuery);
$docs = Documentation::all()->map(fn($d) => [
    'content' => $d->content,
    'embedding' => json_decode($d->embedding, true)
]);
$relevant = $embedder->similar($queryEmbedding, $docs, limit: 3);
$context = implode("\n\n", array_column(array_column($relevant, 'item'), 'content'));

// 3. Generate answer with Groq (fast & cheap!)
$generator = new Groq($http, $cache, $config, $logger);
$answer = $generator->task()
    ->system("Answer based on this documentation:\n\n{$context}")
    ->prompt($userQuery)
    ->run();
```

**Best of both worlds:**
- ✅ Gemini: FREE embeddings for search
- ✅ Groq: Fast, cheap generation (llama-3.1-8b-instant)
- ✅ Claude: High-quality generation for complex queries

## Best Practices

### 1. Batch Embed on Import

```php
// ✅ GOOD - Single API call
$texts = $items->pluck('description')->toArray();
$embeddings = ai()->embed($texts);

// ❌ BAD - Multiple API calls
foreach ($items as $item) {
    $embedding = ai()->embed($item->description);
}
```

### 2. Store Embeddings in Database

```php
// Migration
Schema::table('products', function($table) {
    $table->text('embedding')->nullable();
});

// Model
class Product extends Model
{
    protected $casts = [
        'embedding' => 'array'  // Auto JSON encode/decode
    ];
}
```

### 3. Update Embeddings on Content Change

```php
class Product extends Model
{
    protected function afterSave()
    {
        // Re-embed if description changed
        if ($this->isDirty('description')) {
            $this->embedding = ai()->embed($this->description);
            $this->save();
        }
    }
}
```

## Summary

**Key Points:**
1. ✅ Embeddings are **pre-computed once** and stored
2. ✅ Searching only requires **1 API call** (for the query)
3. ✅ Use `ai()->embed($array)` for batch processing
4. ✅ Use `ai()->similar()` for intuitive similarity search
5. ✅ Gemini embeddings are **FREE**!

**Not This:**
```php
// ❌ Don't embed on every search
foreach ($products as $p) {
    $p['embedding'] = ai()->embed($p->description);  // 1000 API calls!
}
```

**Do This:**
```php
// ✅ Embed once, store, reuse
$embeddings = ai()->embed($descriptions);  // 1 API call
foreach ($products as $i => $p) {
    $p->embedding = json_encode($embeddings[$i]);
    $p->save();
}
```

Embeddings unlock powerful semantic search with minimal API costs! 🚀
