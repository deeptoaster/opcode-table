all: index.html opcode-table.css opcode-table.js

index.html: src/index.php
	cd src && php -f index.php > ../index.html

opcode-table.css: src/opcode-table.css
	postcss src/opcode-table.css --use autoprefixer > opcode-table.css

opcode-table.js: src/opcode-table.ts
	tsc --strict src/opcode-table.ts --outFile opcode-table.js

clean:
	rm -rf index.html opcode-table.css opcode-table.js
