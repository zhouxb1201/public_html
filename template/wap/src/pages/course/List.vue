<template>
  <div class="course-list bg-f8">
    <Navbar />
    <van-search placeholder="课程名称" v-model="params.search_text" @search="onSearch" />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{
        pageType: 'goods', 
        message: '暂无课程', 
        showFoot: true,
        top: $store.state.isWeixin?46:90,
        btnLink: '/', 
        btnText: '返回首页',
      }"
      @load="loadList"
    >
        <div class="list">
          <Card
            v-for="(item,index) in list"
            :key="index"
            :id="'/course/detail/' +item.goods_id"
            :image="item.goods_picture"
            :name="item.goods_name"
            :total_count="item.total_count"
            buttomText="前往学习"
          />
        </div>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { list } from "@/mixins";
import { GET_GOODSLIST } from "@/api/course";
import { Search } from "vant";
import Card from "./component/Card";
export default sfc({
  name: "course-list",
  data() {
    return {
      params: {
        page_index: 1,
        page_size: 14,
        search_text: "",
      },
    };
  },
  mixins: [list],
  mounted() { 
    this.loadList();
  },
  methods: {
    onSearch() {
      this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_GOODSLIST($this.params)
        .then(({ data }) => {
          let list = data.knowledge_payment_list;
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    },
  },
  components: {
    [Search.name]: Search,
    Card,
  }

});
</script>

<style scoped>
.list {
  height: auto;
  overflow: hidden;
  background: #f8f8f8;
  padding: 4px 12px;
}
.list .card-group-box{
  border-radius: 10px;
}
</style>
