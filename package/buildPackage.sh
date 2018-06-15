rm -f ./build/*.zip
zip -r ./build/com_lscache.zip ../com_lscache/*
zip -r ./build/lscache_plugin.zip ../lscache_plugin/*
zip -r ./lscache.zip ./build/*
