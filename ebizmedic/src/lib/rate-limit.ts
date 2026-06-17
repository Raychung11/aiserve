// In-memory rate limiter — good for single-instance Vercel Serverless.
// For multi-instance, swap the Map for a Redis store (Upstash).

interface RateLimitEntry {
  count: number;
  resetAt: number;
}

const store = new Map<string, RateLimitEntry>();

interface RateLimitOptions {
  windowMs: number;  // time window in milliseconds
  max: number;       // max requests per window
}

export function rateLimit(key: string, options: RateLimitOptions): { allowed: boolean; remaining: number; resetAt: number } {
  const now = Date.now();
  const entry = store.get(key);

  if (!entry || entry.resetAt < now) {
    store.set(key, { count: 1, resetAt: now + options.windowMs });
    return { allowed: true, remaining: options.max - 1, resetAt: now + options.windowMs };
  }

  if (entry.count >= options.max) {
    return { allowed: false, remaining: 0, resetAt: entry.resetAt };
  }

  entry.count += 1;
  return { allowed: true, remaining: options.max - entry.count, resetAt: entry.resetAt };
}

// Clean up old entries every 5 minutes to prevent memory leak
setInterval(() => {
  const now = Date.now();
  for (const [key, entry] of store.entries()) {
    if (entry.resetAt < now) store.delete(key);
  }
}, 5 * 60 * 1000);
