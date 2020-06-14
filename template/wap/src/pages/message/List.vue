<template>
  <Layout ref="load" class="message-notice">
    <Navbar :title="hash == 'collect' ? '赞和收藏' : '评论和@'" />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{
        message: '暂无消息通知', 
        showFoot: false,
      }"
      @load="loadList"
    >
      <CellCardGroup
        v-for="(item , index) in list"
        :key="index"
        :id="hash == 'collect' ? item.type_id : item.thing_id"
        :title="item.thing_user_name ? item.thing_user_name : '匿名'"
        :content="item.content"
        :img="item.user_headimg"
        :time="item.create_time"
        :type="item.type ? item.type : ''"
        :cover="item.pic_cover"
        :thing_type="item.thing_type"
        :video_img="item.video_img.pic_cover"
      />
    </List>
  </Layout>
</template>

<script>
import { GET_THINGCIRCLELAC, GET_THINGCIRCLEAT } from "@/api/thingcircle";
import CellCardGroup from "./component/CellCardGroup";
import { list } from "@/mixins";
import sfc from "@/utils/create";
export default sfc({
  name: "message-list",
  data() {
    return {
      params: {
        page_index: 1,
        page_size: 10
      }
    };
  },
  mixins: [list],
  computed: {
    hash() {
      let type;
      const hash = this.$route.hash;
      if (hash == "#collect") {
        type = "collect";
      } else if (hash == "#at") {
        type = "at";
      }
      return type;
    }
  },
  mounted() {
    this.$refs.load.success();
    this.loadList();
  },
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      if ($this.hash == "collect") {
        GET_THINGCIRCLELAC($this.params)
          .then(({ data }) => {
            let list = data.data;
            $this.pushToList(list, data.page_count, init);
          })
          .catch(() => {
            $this.loadError();
          });
      } else if ($this.hash == "at") {
        GET_THINGCIRCLEAT($this.params)
          .then(({ data }) => {
            let list = data.data;
            $this.pushToList(list, data.page_count, init);
          })
          .catch(() => {
            $this.loadError();
          });
      }
    }
  },
  components: {
    CellCardGroup
  }
});
</script>
<style scoped>
</style>