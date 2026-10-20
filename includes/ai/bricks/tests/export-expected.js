/**
 * Run the builder's JS compiler (../html-to-bricks-translation.php, unchanged)
 * on every samples/*.html and write expected/<name>.json.
 *
 *   cd includes/ai/bricks/tests && npm install && npm run expected
 *
 * The PHP port must reproduce these files: php side is run-parity.php.
 * Ids are random on both sides, so both outputs go through normalize() below
 * (run-parity.php applies the same rules) before they are compared.
 */
const fs   = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const SRC = path.join(__dirname, '..', 'html-to-bricks-translation.php');

function loadCompiler() {
    const dom = new JSDOM('<!DOCTYPE html><html><body></body></html>');
    const w   = dom.window;
    // The compiler is a script fragment inside a PHP file: drop the PHP guard line.
    const code = fs.readFileSync(SRC, 'utf8').replace(/^<\?php[^\n]*\?>\s*/, '');
    const factory = new Function(
        'window', 'document', 'DOMParser', 'XMLSerializer', 'NodeFilter', 'Node', 'ChatState',
        code + '\nreturn { compileHtmlToBricksJson };'
    );
    return factory(
        w, w.document, w.DOMParser, w.XMLSerializer, w.NodeFilter, w.Node,
        { globalUsedIds: new Set() }
    );
}

/** Replace random ids with stable placeholders, in order of first appearance. */
function normalize(result) {
    const map = {};
    let e = 0, c = 0, a = 0;
    (result.content || []).forEach(el => { if (!(el.id in map)) map[el.id] = 'E' + (++e); });
    (result.globalClasses || []).forEach(gc => { if (!(gc.id in map)) map[gc.id] = 'C' + (++c); });
    (result.content || []).forEach(el => ((el.settings && el.settings._attributes) || []).forEach(at => {
        if (at._id && !(at._id in map)) map[at._id] = 'A' + (++a);
    }));
    const walk = v => {
        if (Array.isArray(v)) return v.map(walk);
        if (v && typeof v === 'object') {
            const out = {};
            Object.keys(v).forEach(k => { out[k] = walk(v[k]); });
            return out;
        }
        if (typeof v === 'string' && Object.prototype.hasOwnProperty.call(map, v)) return map[v];
        return v;
    };
    return { content: walk(result.content), globalClasses: walk(result.globalClasses) };
}

const { compileHtmlToBricksJson } = loadCompiler();
const dir = path.join(__dirname, 'samples');
const out = path.join(__dirname, 'expected');
fs.mkdirSync(out, { recursive: true });

let n = 0;
fs.readdirSync(dir).filter(f => f.endsWith('.html')).sort().forEach(file => {
    const html   = fs.readFileSync(path.join(dir, file), 'utf8');
    const result = compileHtmlToBricksJson(html, null, null, {});
    fs.writeFileSync(path.join(out, file.replace(/\.html$/, '.json')), JSON.stringify(normalize(result), null, 2) + '\n');
    n++;
});
console.log('Wrote ' + n + ' expected file(s) to ' + out);

module.exports = { normalize };
