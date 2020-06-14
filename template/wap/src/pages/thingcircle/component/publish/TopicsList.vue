<template>
  <div>
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{message: '没有这个话题', showFoot: false}"
      @load="loadList"
    >
      <div class="list">
        <van-cell
          :title=" '#' +item.topic_title"
          v-for="(item,index) in list"
          :key="index"
          @click="onAddTopics(item.topic_title,item.topic_id)"
        />
      </div>
    </List>
  </div>
</template>

<script>
import { GET_TOPICLIST, GET_NEXTTOPICLIST } from "@/api/thingcircle";
import { list } from "@/mixins";
export default {
  data() {
    return {
      params: {
        page_index:1,
        page_size: 10
      }
    };
  },
  props: {
    id: [Number, String],
    search_text: [String],
    state: [Number, String]
  },
  mixins: [list],
  mounted() {
    this.loadList();
  },
  watch: {
    id(value) {
      if (this.state == 1) {
        this.params.superiors_id = value;
      }
      this.params.search_text = "";
      this.loadList("init");
    },
    search_text(value) {
      if (this.state == 1) {
        this.params.superiors_id = "";
      }
      this.params.search_text = value;
      this.loadList("init");
    }
  },
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      if ($this.state == 1) {
        GET_NEXTTOPICLIST($this.params).then(({ data }) => {
          let list = data.data;
          $this.pushToList(list, data.page_count, init);
        });
      } else {
        GET_TOPICLIST($this.params).then(({ data }) => {
          let list = data.data;
          $this.pushToList(list, data.page_count, init);
        });
      }
    },
    onAddTopics(title, id) {
      let topics = {
        title: title,
        topic_id: id
      };
      this.$emit("add-topics", topics);
    }
  }
};
</script>

<style scoped>
.list {
  position: relative;
}
.list >>> .empty {
  top: 160px;
}
</style>