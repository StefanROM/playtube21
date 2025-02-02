const pool = mysql.createPool({
    host: DB_CREDENTIALS.DBHOST,
    user: DB_CREDENTIALS.DBUSER,
    password: DB_CREDENTIALS.DBPASS,
    database: DB_CREDENTIALS.DBNAME
});

exports.connection = {
    query: function () {
        let queryArgs = Array.prototype.slice.call(arguments),
            events = [],
            eventNameIndex = {};

        pool.getConnection(function (err, conn) {
            if (err) {
                if (eventNameIndex.error) {
                    eventNameIndex.error();
                }
            }
            if (conn) { 
                let q = conn.query.apply(conn, queryArgs);
                q.on('end', function () {
                    conn.release();
                });

                events.forEach(function (args) {
                    q.on.apply(q, args);
                });
            }
        });

        return util.promisify(this.query).bind(m_connection);{
            on: function (eventName, callback) {
                events.push(Array.prototype.slice.call(arguments));
                eventNameIndex[eventName] = callback;
                return this;
            }
        };
    }
};