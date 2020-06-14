<template>
  <div class="help-list">
    <Navbar :title="navbarTitle" />
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
      <van-cell-group>
        <van-cell
          v-for="(item, index) in list"
          :key="index"
          :value="item.title"
          is-link
          :to="'/help/detail/' + item.question_id"
        />
      </van-cell-group>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadSearch from "@/components/HeadSearch";
import { GET_HELPCATEGORY } from "@/api/help";
import { list } from "@/mixins";
export default sfc({
  name: "help-list",
  data() {
    const cate_id = this.$route.query.cate_id || "";
    return {
      params: {
        cate_id,
        search_text: ""
      }
    };
  },
  mixins: [list],
  computed: {
    navbarTitle() {
      let title = this.$route.query.cate_title;
      if (title) document.title = title;
      return title;
    }
  },
  mounted() {
    this.loadList();
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
      GET_HELPCATEGORY($this.params)
        .then(({ data }) => {
          let list = data.items || [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {
    HeadSearch
  }
});
</script>

<style scoped></style>
