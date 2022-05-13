squiffles:
	cd src && php -f index.php > ../index.html
	postcss src/opcode_table.css --use autoprefixer > opcode_table.css

clean:
	rm -rf index.html opcode_table.css
