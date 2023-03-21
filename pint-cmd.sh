if [ -f ./vendor/bin/pint ] ; then
    php ./vendor/bin/pint --config ./pint.json "${@}"
fi
