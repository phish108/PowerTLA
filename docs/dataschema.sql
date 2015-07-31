-- NOTE this schema


create table if not exists pwrtla_tokens (         -- OAUTH Token store
    token_id     varchar(255) PRIMARY KEY,         -- shared id (token in Bearer)
    token_type   varchar(255) NOT NULL,            -- type in [Bearer, Client, MAC]
    token_key    varchar(255),                     -- private key (only shared once)
    client_id    varchar(255),                     -- client identifier, e.g., device UUID
    user_id      INT,                              -- internal user id if type = Bearer or MAC
    doamin       varchar(255),                     -- reverse identifier of the app
    extra        TEXT                              -- extra token settings
);

create table if not exists pwrtla_authpins (       -- used for one-time authentification pins
    login        varchar(255) NOT NULL UNIQUE,     -- user name
    pinhash      varchar(255) NOT NULL,            -- sha1 hash of the generated pin code
    created      INT NOT NULL                      -- timestamps for invalidating old pins
);

create table if not exists pwrtla_usertokens(      -- used for public profile to obscure internal ids
    user_id      INT          PRIMARY KEY,         -- the internal user id
    user_token   varchar(255) NOT NULL UNIQUE      -- the external key for linking
);

create table if not exists pwrtla_xapistatements ( -- XAPI LRS
    id           VARCHAR(255) PRIMARY KEY,         -- statement id
    statement    TEXT NOT NULL,                    -- the actual statement JSON
    stored       INT NOT NULL,                     -- the timestamp of arrival
    user_id      INT NOT NULL,                     -- the internal user id of the actor
    registration varchar(255)                      -- registration context (if set)
);


create table if not exists pwrtla_xapicontexts (   -- for fast context retrieval
    id           varchar(255) not null,            -- reference to a LRS statement.id
    context_type varchar(255) not null,            -- the name of the context (e.g., "parent")
    context_id   varchar(255) not null             -- the IRI/UUID value of the context
);

-- THE SETTINGS ARE STORED AS A SPECIAL DOCUMENT
-- id = agent = agenthash = "PowerTLA Settings"
create table if not exists pwrtla_xapidocuments ( -- XAPI Document Repository
    id           VARCHAR(255) PRIMARY KEY,        -- document id
    document     TEXT         NOT NULL,           -- the document JSON
    doctype      varchar(255) NOT NULL,           -- type in [agents_profile, actitites_profile, activities_state]
    agent        TEXT         NOT NULL,           -- the agent/actor JSON string
    agenthash    varchar(255) NOT NULL,           -- agent hash (for faster retrieval)
    activityid   varchar(255)                     -- reference to a LRS statement.id
);

-- HELPER RELATIONS FOR FILTERS
create table if not exists pwrtla_xapifilterindex (
    id           varchar(255) not null,            -- reference to a LRS statement.id
    filter_id    varchar(255) not null,            -- reference to a xapi filter
    propertyhash varchar(255)                      -- hash for the combined filter variables for the statement
);

create table if not exists pwrtla_xapifilters (
    filter_id    varchar(255) primary key,         -- filter id
    filter       text         not null,            -- the filter definition
    created      int          not null
);