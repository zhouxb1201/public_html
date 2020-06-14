<template>
  <div class="help-index">
    <template v-if="$store.state.config.addons.helpcenter">
      <Navbar />
      <HeadSearch
        :disabled="false"
        show-action
        placeholder="请输入搜索关键词"
        :top="$store.state.isWeixin ? '0' : '46px'"
        @rightAction="onSearch"
      />
      <List
        v-model="loading"
        :finished="finished"
        :error.sync="error"
        :is-empty="isListEmpty"
        :empty="{
          message: '暂无相关帮助内容',
          top: $store.state.isWeixin ? 46 : 90
        }"
        @load="loadList"
      >
        <div class="cell-group" v-for="(item, index) in list" :key="index">
          <div class="title-group van-hairline--right">
            <div class="title">{{ item.name }}</div>
            <router-link
              class="a-link more"
              :to="{
                name: 'help-list',
                query: {
                  cate_id: item.cate_id,
                  cate_title: item.name
                }
              }"
              >更多></router-link
            >
          </div>
          <div class="content-group">
            <router-link
              tag="div"
              class="item"
              v-for="(child, c) in item.items"
              :key="c"
              :to="'/help/detail/' + child.id"
            >
              {{ child.title }}
            </router-link>
          </div>
        </div>
      </List>
    </template>
    <Empty
      v-else
      page-type="fail"
      message="未开启帮助中心应用"
      :show-foot="false"
    />
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadSearch from "@/components/HeadSearch";
import { GET_HELPLIST, GET_HELPCATEGORY, GET_HELPDETAIL } from "@/api/help";
import { list } from "@/mixins";
import Empty from "@/components/Empty";
export default sfc({
  name: "help-index",
  data() {
    return {
      params: {
        search_text: ""
      }
    };
  },
  mixins: [list],
  computed: {},
  mounted() {
    this.$store.state.config.addons.helpcenter && this.loadList();
  },
  methods: {
    onSearch(e) {
      this.params.search_text = e;
      this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_HELPLIST($this.params)
        .then(({ data }) => {
          let list = data.c_data || [];
          $this.pushToList(list, data.c_page, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {
    HeadSearch,
    Empty
  }
});
</script>

<style scoped>
.cell-group {
  background: #fff;
  margin: 10px 0;
  display: flex;
  flex-flow: row;
}
.title-group {
  padding: 10px;
  width: 30%;
  display: flex;
  flex-flow: column;
  line-height: 20px;
}
.title-group .title {
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}
.title-group .more {
  font-size: 12px;
}
.content-group {
  padding: 5px;
  width: 70%;
  display: flex;
  flex-wrap: wrap;
}
.content-group .item {
  margin: 1%;
  width: 48%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 4px;
  height: 24px;
  line-height: 1;
  border: 1px solid #f1f1f1;
}
</style>
