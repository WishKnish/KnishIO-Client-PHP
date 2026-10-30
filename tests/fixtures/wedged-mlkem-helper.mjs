// A helper that never answers and ignores the end of its stdin, as a wedged one would.
process.stdin.on('data', () => {});
process.stdin.on('end', () => {});
setInterval(() => {}, 1000);
