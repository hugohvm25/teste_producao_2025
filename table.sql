-- tabela status
create table status (
    id_status int auto_increment primary key,
    description varchar(255) not null
);

-- tabela prompt
create table prompt (
    id_prompt int auto_increment primary key,
    prompt_type varchar(50) not null,
    description varchar(255),
    prompt_text text,
    date_created timestamp default current_timestamp
);

-- tabela fluxo
create table fluxo (
    id_fluxo int auto_increment primary key,
    id_user int not null,
    id_course int not null,
    id_status int,
    pref_type int,
    date_created timestamp default current_timestamp,
    foreign key (id_status) references status(id_status)
);
