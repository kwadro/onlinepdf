def connect_database(mode):
    if (mode=='local') :
        return "symfony","root","root","127.0.0.1",3308

    if (mode=='prod'):
        return "kwadro_laravel1","kwadro_laravel1","3G7m2cxE9M","kwadro.mysql.tools",3306

    if (mode=='stage'):
        return "kwadro_laravel","kwadro_laravel","y22KN_t+u8","kwadro.mysql.tools",3306

    raise ValueError(f"Unknown environment: {mode}")
